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

## Имейли

Всяка изпратена форма — оферта от началната страница, съобщение от `/contact`
и запитване от страница на услуга — се записва в базата и веднага след това
тръгва като имейл до офиса (`app/Mail/InquiryReceived.php`). Изпраща се от
`noreply@gcargosped.com`, но `Reply-To` е адресът на клиента, така че отговорът
от пощата отива директно при него.

Изпращането е синхронно, не през опашка: няма queue worker в продукция, а
обемът е няколко запитвания на ден. Ако доставчикът гръмне, запитването остава
в базата и си стои в `/admin/inquiries` — грешката влиза в
`storage/logs/laravel.log` като `Inquiry notification failed`, а клиентът вижда
нормалното съобщение за успех.

### Настройка на Resend

1. В [resend.com](https://resend.com) → **Domains** → добави `gcargosped.com`,
   регион **EU (Ireland)**.
2. Добави трите записа, които таблото показва. Стойностите са уникални за
   акаунта — копирай ги оттам, не от този файл:

   | Type | Host | Стойност |
   |---|---|---|
   | MX | `send` | `feedback-smtp.eu-west-1.amazonses.com.` (priority 10) |
   | TXT | `send` | `v=spf1 include:amazonses.com ~all` |
   | TXT | `resend._domainkey` | `p=MIGfMA0GCSq…` |

   В Namecheap MX не е в списъка с типове при Host Records — управлява се
   отделно, в секция **MAIL SETTINGS** → **Custom MX** на същата страница.
   Превключването е за цялата зона, така че ако домейнът приема поща, старите
   MX записи трябва да се добавят наново заедно с този.

3. Изчакай статусът да стане **Verified**. Проверка отвън:

   ```bash
   dig +short MX send.gcargosped.com
   dig +short TXT send.gcargosped.com
   dig +short TXT resend._domainkey.gcargosped.com
   ```

4. **API Keys** → нов ключ с права *Sending access*.

### В продукция

```
MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxx
MAIL_FROM_ADDRESS="noreply@gcargosped.com"
MAIL_FROM_NAME="Глобал Карго Спед"
MAIL_INQUIRY_TO=gcargosped@gmail.com
```

`MAIL_FROM_ADDRESS` е самоличността на изпращача и трябва да е на верифицирания
домейн; `MAIL_INQUIRY_TO` е пощата, в която падат запитванията.

Деплоят трябва да мине през `composer install` — `resend/resend-laravel` е
зависимост. И задължително след промяна на `.env`:

```bash
php artisan config:clear && php artisan config:cache
```

Продукцията държи кеширан конфиг, така че само редакция на `.env` не променя
нищо.

### Локално

```
MAIL_MAILER=log
```

Имейлите отиват в `storage/logs/laravel.log` вместо навън. За реална проба
насочи временно `MAIL_INQUIRY_TO` към собствения си адрес — иначе тестовите
запитвания стигат до офиса.

## WhatsApp бутон

Долу вдясно на всяка страница стои зелено кръгче. Отваря меню с готови теми и
всяка от тях стартира чат в WhatsApp със самата тема като първо съобщение —
офисът вижда за какво пита клиентът още преди той да е казал „здравейте“.

Управлява се изцяло от **Настройки на сайта → WhatsApp бутон**: номер, надпис,
подканващо балонче и списъкът с теми. **Празен номер = бутонът не се показва**,
така че секцията може да се попълни и след пускането на сайта.

Всяко натискане се записва в `whatsapp_clicks` (тема + страница, от коя е дошло
запитването) и излиза на таблото в администрацията. Разговорът продължава в
телефона на офиса, където не виждаме нищо — кликът е единственото измеримо нещо.

Заявката тръгва с `navigator.sendBeacon`, докато браузърът вече отива към
`wa.me`. sendBeacon не може да сложи CSRF хедър, затова `track/whatsapp` е
изваден от проверката в `bootstrap/app.php` и е ограничен на 20 заявки в минута.

Какво остава за клиента — WhatsApp Business, автоматичен поздрав, съобщение
извън работно време — е описано на човешки език в
[`docs/whatsapp-nastroyka.md`](docs/whatsapp-nastroyka.md).

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
(телефон, имейл, адрес, hero текстове, текстовете около формата за запитване,
WhatsApp бутона, SEO) и получените запитвания.

Текстовете над кратката форма и благодарността след изпращане се редактират в
**Настройки на сайта → Форма за запитване**. Началната страница и страниците на
услугите ползват едни и същи — обещание за срок на отговор, дадено на едното
място и забравено на другото, е точно това, което тези полета премахват.

`database/data/legacy-content.json` е изворът за сийдъра — съдържанието,
извлечено от стария статичен сайт.
