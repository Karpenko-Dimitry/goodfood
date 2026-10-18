# DietCoach — Laravel 12

Сайт о питании по мотивам шаблона [Diet Coach #446293](https://demo.templatemonster.com/ru/demo/446293.html):
коллекция популярных диет, рецепты с фото и КБЖУ, персональная диета от OpenAI и админка.

**Стек:** PHP 8.2+ · Laravel 12 · Blade + Tailwind CSS 4 · Filament 5 (админка) · Claude через `anthropic-ai/sdk`, фото через `openai-php/client` ·
`spatie/laravel-translation-loader` (UI-строки в БД) · `astrotomic/laravel-translatable` (контент на 3 языках).

## Быстрый старт

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
composer run dev        # сервер + очередь + vite
```

- Сайт: http://localhost:8000 (редирект на `/ru`, `/en` или `/uk` по Accept-Language)
- Админка: http://localhost:8000/admin — `admin@dietcoach.test` / `password` (создаётся сидером, смените в проде)

## AI

Вся работа с моделями — в сервисе `App\Services\Ai\NutritionAi`. Текст (план и рецепты) генерирует Claude через
официальный `anthropic-ai/sdk` со структурированным JSON-ответом (`App\Ai\Providers\ClaudeTextModel`, при отказе
модели включён серверный fallback), фото — OpenAI через `openai-php/client` (`OpenAiImageModel`; Claude изображения
не генерирует). Провайдеры подключаются через контракты `App\Ai\Contracts\TextModel` / `ImageModel`.

```
ANTHROPIC_API_KEY=
OPENAI_API_KEY=            # только для фото; без ключа блюда сохраняются без фото
AI_DIET_MODEL=claude-opus-5-5
AI_DISH_MODEL=             # пусто = AI_DIET_MODEL
AI_EFFORT=medium           # low | medium | high | xhigh | max
AI_IMAGE_MODEL=gpt-image-1
AI_IMAGES_ENABLED=true
AI_NEW_DISHES_PER_PLAN=6   # сколько новых уникальных блюд добавляется в базу при каждом плане
AI_PUBLISH_NEW_DISHES=true # false — новые блюда скрыты до модерации в админке
```

Цепочка генерации (всё в очереди — нужен воркер, `composer run dev` его запускает):

1. `GenerateDietPlan` — `NutritionAi::mealPlan()` составляет меню на 7 дней и обязательно придумывает
   ровно `AI_NEW_DISHES_PER_PLAN` новых блюд (поле `new_dishes`, размер зафиксирован в JSON-схеме);
   модели передаётся список уже существующих названий.
2. `CreateAiDish` на каждое новое блюдо — `AiDishCreator` через `NutritionAi::dishRecipe()` (агент `DishChef`)
   получает рецепт на ru/en/uk с ингредиентами, шагами, КБЖУ, категорией и диетами. Если название уже есть
   в базе (на любом языке), модель получает список занятых названий и пишет другое блюдо (до 3 попыток).
   Финальная проверка и сохранение идут под блокировкой. Блюдо сохраняется (`source = ai`) и связывается
   с планом (`diet_plan_request_dish`).
3. `GenerateDishImage` — `NutritionAi::dishImage()` генерирует фото. Если не удалось, блюдо остаётся с заглушкой,
   фото можно сделать позже кнопкой «Фото через AI».

Страница плана сама обновляется по мере готовности рецептов. В админке: «Блюда → Создать блюдо через AI»,
фильтр «Источник», в карточке AI-плана — список созданных для него блюд.

## Что внутри

| Раздел | Где |
|---|---|
| Локали `ru`, `en`, `uk` | `config/app.php` → `locales`; префикс `/{locale}` и `App\Http\Middleware\SetLocale` |
| UI-строки | `lang/{locale}/site.php` → импорт в БД `php artisan translations:import [--force]`, редактирование в админке «Локализация → Переводы интерфейса» (строка из БД перекрывает файл) |
| Контент на 3 языках | модели `Diet`, `Dish`, `DishCategory` с переводами в таблицах `*_translations` (`astrotomic/laravel-translatable`, модели `*Translation`, фолбэк на `en`); в админке — вкладка на каждый язык |
| Диеты | 10 шт.: средиземноморская, кето, IF 16/8, веганская, палео, DASH, низкоуглеводная, высокобелковая, флекситарианство, скандинавская |
| Блюда | 26 рецептов в 5 категориях: фото, ингредиенты, шаги, КБЖУ на порцию, время, привязка к диетам; фильтры по категории/диете/калориям, поиск |
| AI | сервис `NutritionAi`, агенты `DietPlanner` (план) и `DishChef` (рецепт), `AiDishCreator` (сохранение новых блюд), задачи `GenerateDietPlan` → `CreateAiDish` → `GenerateDishImage` — см. раздел «AI» |
| Админка (Filament) | Диеты, Блюда (+ «Фото через AI», калькулятор ккал из БЖУ), Категории, AI-планы (просмотр, перезапуск), Переводы интерфейса (+ импорт из файлов), виджет статистики |

Все изображения хранятся локально на диске `public` (`storage/app/public/{diets,dishes}`, нужен `php artisan storage:link`):
загруженные в админке, сгенерированные ИИ и демо-фото. Демо-фото описаны в сидерах как ссылки Unsplash (`image_url`) —
`php artisan db:seed` сам скачивает их командой `php artisan images:localize` (повторный запуск докачивает только
недостающие, `--force` — скачать заново). Поле `image` (локальный файл) имеет приоритет над `image_url`.

## Посетители (онлайн, новые, гости/пользователи)

Middleware `TrackVisitor` (группа `web` и панель `/admin`) вызывает сервис `App\Services\Visitors\VisitorTracker`:
каждый браузер получает cookie `dc_vid` и одну строку в таблице `visitors` — первый/последний визит, просмотры,
страница входа и последняя страница, источник, язык, устройство, анонимизированный IP. Когда гость входит в
систему, строка привязывается к пользователю. Боты, JSON/AJAX-запросы, POST и ответы с ошибкой не учитываются.

В админке: «Аналитика → Посетители» (онлайн-индикатор, фильтры «Сейчас онлайн», «Новые сегодня», гости/пользователи,
счётчик онлайн в меню) и виджет «Посетители» на главной панели (онлайн сейчас, новые за сегодня, новые пользователи за 7 дней).

Настройки — `config/visitors.php` (`VISITORS_ONLINE_MINUTES=5`, `VISITORS_RETENTION_DAYS=90`). Старые записи удаляет
`php artisan model:prune` по расписанию — на сервере нужен cron: `* * * * * php /path/to/artisan schedule:run`.

## Тесты

```bash
php artisan test
```

Покрывают все публичные страницы во всех локалях, фильтры, переопределение перевода из БД,
полный цикл AI (план → новые блюда → фото, дедупликация, сбои провайдера) через `App\Ai\Testing\FakeAi` без реальных запросов, валидацию и страницы/сохранение в админке.
