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

Для AI-диеты пропишите ключ в `.env`:

```
OPENAI_API_KEY=sk-...
AI_DIET_MODEL=gpt-4.1-mini     # модель для плана питания
AI_IMAGE_MODEL=gpt-image-1     # модель для «Фото через AI» в админке
```

Генерация идёт в очереди (`GenerateDietPlan`), поэтому нужен воркер — `composer run dev` уже запускает
`queue:listen`; в проде — `php artisan queue:work` (или `QUEUE_CONNECTION=sync` для простоты).

## Что внутри

| Раздел | Где |
|---|---|
| Локали `ru`, `en`, `uk` | `config/app.php` → `locales`; префикс `/{locale}` и `App\Http\Middleware\SetLocale` |
| UI-строки | `lang/{locale}/site.php` → импорт в БД `php artisan translations:import [--force]`, редактирование в админке «Локализация → Переводы интерфейса» (строка из БД перекрывает файл) |
| Контент на 3 языках | модели `Diet`, `Dish`, `DishCategory` (JSON-колонки, `HasTranslations`); в админке — вкладка на каждый язык |
| Диеты | 10 шт.: средиземноморская, кето, IF 16/8, веганская, палео, DASH, низкоуглеводная, высокобелковая, флекситарианство, скандинавская |
| Блюда | 26 рецептов в 5 категориях: фото, ингредиенты, шаги, КБЖУ на порцию, время, привязка к диетам; фильтры по категории/диете/калориям, поиск |
| AI-диета | `App\Ai\Agents\DietPlanner` — агент `laravel/ai` со структурированным выводом (JSON-схема: 7 дней, приёмы пищи, БЖУ, список покупок, советы). В промпт передаются анкета, расчёт по Миффлину–Сан Жеору и каталог рецептов сайта, чтобы модель ссылалась на них |
| Админка (Filament) | Диеты, Блюда (+ «Фото через AI», калькулятор ккал из БЖУ), Категории, AI-планы (просмотр, перезапуск), Переводы интерфейса (+ импорт из файлов), виджет статистики |

Фото в сидерах — внешние ссылки Unsplash (`image_url`); загруженный в админке файл (`image`) имеет приоритет.

## Тесты

```bash
php artisan test
```

Покрывают все публичные страницы во всех локалях, фильтры, переопределение перевода из БД,
полный цикл AI-плана через `DietPlanner::fake()` (без запросов к OpenAI), валидацию и страницы/сохранение в админке.
