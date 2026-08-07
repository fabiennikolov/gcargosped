# Глобал Карго Спед

Публичен сайт и администрация на [gcargosped.com](https://www.gcargosped.com) — транспорт,
спедиция и логистика.

## Стек

| | |
|---|---|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Inertia 2 + React 19 + TypeScript, Vite |
| Администрация | Filament v5 (`/admin`) |
| База | SQLite |
| Рендиране | Inertia SSR — сървърно рендиран HTML за търсачките |

Дизайнът е пренесен от предишния статичен сайт. `resources/css/site.css` е онзи
CSS, скоупнат под `.gcs`, за да не се смесва с Tailwind. **Публичните страници
съзнателно не зареждат `app.css`** — Tailwind preflight е глобален ресет, който
този дизайн не очаква. Виж бележката в `resources/js/app.tsx`.

## URL адреси

Пътищата повтарят едно към едно тези на стария сайт, за да не се губи
индексирането в Google:

```
/                          начало
/about                     за нас
/services                  услуги
/service/{slug}            3 основни услуги
/sub-services/{slug}       17 специализирани превоза
/blog        /blog/{slug}  блог
/contact                   заявка за оферта
/sitemap.xml               генерира се от базата
/admin                     Filament
```

## Първоначална настройка

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=LegacyContentSeeder   # услуги, партньори, настройки

npm run build:ssr      # клиентски бъндъл + SSR бъндъл
php artisan storage:link
```

### Администратор

`make:filament-user` **не задава** флага `is_admin`, на който панелът стъпва — с
него влизането минава, но панелът връща 403. Ползвай:

```bash
php artisan app:make-admin
php artisan app:make-admin --name="Име" --email="ти@имейл" --password="…"
```

Командата създава нов или повишава съществуващ акаунт.

## SSR

Без работещ SSR процес страниците се рендират само в браузъра — заглавията и
описанията не стигат до търсачките и до споделянията в социалните мрежи.

```bash
php artisan inertia:start-ssr
```

В продукция върви като услуга — виж `deploy/gcargosped-ssr.service`:

```bash
cp deploy/gcargosped-ssr.service /etc/systemd/system/
systemctl daemon-reload && systemctl enable --now gcargosped-ssr
```

В `.env`:

```
INERTIA_SSR_ENABLED=true
INERTIA_SSR_URL=http://127.0.0.1:13714
```

## Тестове

```bash
composer test
```

Пуска се през composer скрипта, а не направо `php artisan test`: продукцията
държи кеширан конфиг (`config:cache`), който заобикаля `phpunit.xml` и насочва
`RefreshDatabase` към живата база. `tests/TestCase.php` отказва да стартира,
ако връзката не е `:memory:`, но скриптът така или иначе първо чисти кеша.

## Съдържание

Всичко редактируемо е в администрацията: услуги (текст, снимка, подредба,
основна/специализирана), блог с категории, партньори, настройки на сайта
(телефон, имейл, адрес, hero текстове, SEO) и получените запитвания.

`database/data/legacy-content.json` е изворът за сийдъра — съдържанието,
извлечено от стария статичен сайт.
