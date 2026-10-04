# DietCoach — Laravel 12

Сайт о питании по мотивам шаблона [Diet Coach #446293](https://demo.templatemonster.com/ru/demo/446293.html):
коллекция популярных диет, рецепты с фото и КБЖУ, персональная диета от OpenAI и админка.

**Стек:** Laravel 12 · Blade + Tailwind CSS 4 · Filament 5 (админка) · `laravel/ai` (OpenAI) ·
`spatie/laravel-translation-loader` (UI-строки в БД) · `spatie/laravel-translatable` (контент на 3 языках).

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

Вся работа с моделями — в сервисе `App\Services\Ai\NutritionAi` (провайдер и модели из `config/services.php` → `ai`).
По умолчанию берётся провайдер из `config/ai.php` (`default`, `default_for_images`).

```
OPENAI_API_KEY=            # и/или
ANTHROPIC_API_KEY=
AI_TEXT_PROVIDER=          # openai | anthropic | gemini … (пусто = config/ai.php)
AI_DIET_MODEL=             # модель для плана (пусто = модель провайдера по умолчанию)
AI_DISH_MODEL=             # модель для рецептов новых блюд (пусто = AI_DIET_MODEL)
AI_IMAGE_PROVIDER=         # провайдер фото (Claude фото не генерирует — нужен openai/gemini)
AI_IMAGE_MODEL=
AI_IMAGES_ENABLED=true     # false — новые блюда сохраняются без фото
AI_NEW_DISHES_PER_PLAN=6   # сколько новых блюд максимум создаётся на один план
AI_PUBLISH_NEW_DISHES=true # false — новые блюда скрыты до модерации в админке
```

Цепочка генерации (всё в очереди — нужен воркер, `composer run dev` его запускает):

1. `GenerateDietPlan` — `NutritionAi::mealPlan()` составляет меню на 7 дней: часть приёмов пищи из каталога сайта,
   часть — новые блюда, которых в базе нет.
2. `CreateAiDish` на каждое новое блюдо — `AiDishCreator` проверяет дубликаты (по названию на любом языке),
   `NutritionAi::dishRecipe()` (агент `DishChef`) пишет рецепт на ru/en/uk с ингредиентами, шагами, КБЖУ,
   категорией и диетами; блюдо сохраняется (`source = ai`) и связывается с планом (`diet_plan_request_dish`).
3. `GenerateDishImage` — `NutritionAi::dishImage()` генерирует фото. Если не удалось, блюдо остаётся с заглушкой,
   фото можно сделать позже кнопкой «Фото через AI».

Страница плана сама обновляется по мере готовности рецептов. В админке: «Блюда → Создать блюдо через AI»,
фильтр «Источник», в карточке AI-плана — список созданных для него блюд.

## Что внутри

| Раздел | Где |
|---|---|
| Локали `ru`, `en`, `uk` | `config/app.php` → `locales`; префикс `/{locale}` и `App\Http\Middleware\SetLocale` |
| UI-строки | `lang/{locale}/site.php` → импорт в БД `php artisan translations:import [--force]`, редактирование в админке «Локализация → Переводы интерфейса» (строка из БД перекрывает файл) |
| Контент на 3 языках | модели `Diet`, `Dish`, `DishCategory` (JSON-колонки, `HasTranslations`); в админке — вкладка на каждый язык |
| Диеты | 10 шт.: средиземноморская, кето, IF 16/8, веганская, палео, DASH, низкоуглеводная, высокобелковая, флекситарианство, скандинавская |
| Блюда | 26 рецептов в 5 категориях: фото, ингредиенты, шаги, КБЖУ на порцию, время, привязка к диетам; фильтры по категории/диете/калориям, поиск |
| AI | сервис `NutritionAi`, агенты `DietPlanner` (план) и `DishChef` (рецепт), `AiDishCreator` (сохранение новых блюд), задачи `GenerateDietPlan` → `CreateAiDish` → `GenerateDishImage` — см. раздел «AI» |
| Админка (Filament) | Диеты, Блюда (+ «Фото через AI», калькулятор ккал из БЖУ), Категории, AI-планы (просмотр, перезапуск), Переводы интерфейса (+ импорт из файлов), виджет статистики |

Фото в сидерах — внешние ссылки Unsplash (`image_url`); загруженный в админке файл (`image`) имеет приоритет.

## Тесты

```bash
php artisan test
```

Покрывают все публичные страницы во всех локалях, фильтры, переопределение перевода из БД,
полный цикл AI (план → новые блюда → фото, дедупликация, сбои провайдера) через fake-агенты без реальных запросов, валидацию и страницы/сохранение в админке.
