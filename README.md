## Web приложение "Файловый менеджер".

---

Описание проекта и особенности приложения.

Используемые техники в Laravel

---

**Стек:**

- 🐘 **Php 8.2** + **Laravel 10** with **Laravel/Breeze** **InertiaJs**
- 🌊 **Tailwind CSS** - css фреймворк
- 🥉**VueJs** + **InertiaJs** - фронт приложение 
- 🦖 **MariaDb** - основная база
- 🗃 **MinIO** - объектное хранилище, совместимое с Amazon S3 API
- 🐋 **Docker**, **Laravel Sail** - для локальной разработки.
-------

### Установка проекта

Для развертывания проекта потребуется установленный
🐳 **docker** или же 🐋 **docker desktop** проект будет работать
как на Windows с поддержкой WSL2 так и на Linux машине.

Локальная разработка и тестирование проекта использует
легковесный [Laravel Sail](https://laravel.com/docs/9.x/sail)
для работы с docker контейнерами.

Настроить переменные окружения (если требуется изменить их):

```shell
cp .env.example .env
```

Установить зависимости проекта:

```shell
docker run --rm -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

на этом подготовка к работе с Laravel Sail закончена.

### Запуск проекта

Поднять docker контейнеры с помощтю Laravel Sail

```shell
./vendor/bin/sail up -d
```

доступные команды по остановке или пересборке контейнеров можно узнать на странице
[Laravel Sail](https://laravel.com/docs/10.x/sail)
или выполните команду `./vendor/bin/sail` для получения краткой справки о доступных командах.

1. Сгенерировать application key
   ```shell
   ./vendor/bin/sail artisan key:generate
   ```

2. Выполнить миграции и заполнить таблицы тестовыми данными
   ```shell
   ./vendor/bin/sail artisan migrate --seed
   ```
3. Собрать фронт
    ```shell
    ./vendor/bin/sail npm install
    ```
    ```shell
    ./vendor/bin/sail npm run build
    ```
4. Запустить воркер (worker) обрабатывающий задачи из очереди сообщений

    ```shell
    ./vendor/bin/sail artisan queue:work --tries=3 --queue=email,default
    ```
   в проекте используется очереди с разными приоритетами.

### Доступные сайты в dev окружении

| Host                               | Назначение                                                   |
|:-----------------------------------|:-------------------------------------------------------------|
| http://localhost                   | сайт приложения                                              |
| http://localhost:8025              | Mailpit - вэб интерфейс для отладки отправки email сообщения |
| http://localhost:8900              | MinIO object store - логин sail, пароль password             |
