# CodePush PHP Server

Сервер для управления обновлениями приложений, совместимый с CodePush и Capgo. Позволяет загружать и распространять обновления для мобильных приложений.

## Возможности

- ✅ Поддержка CodePush API
- ✅ Поддержка Capgo API
- ✅ Загрузка сборок через веб-интерфейс
- ✅ Автоматическое управление версиями
- ✅ Безопасная загрузка с ключом доступа

## Установка

### 1. Клонирование проекта

```bash
git clone https://github.com/AntonSeagull/codepush-php-server.git ./
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Настройка

Откройте файл `index.php` и измените `upload_key` на ваш секретный ключ:

```php
$f3->set('config', [
    'upload_key' => 'your-secret-key-here', // Замените на ваш ключ
    // ... остальные настройки
]);
```

### 4. Настройка веб-сервера

Убедитесь, что ваш веб-сервер настроен для работы с PHP 8.0+ и указывает на `index.php` как точку входа.

## Использование

### Загрузка сборок

#### Автоматическая загрузка через liveupdate-cli (рекомендуется)

Установите CLI инструмент:

```bash
curl -sSL https://raw.githubusercontent.com/AntonSeagull/liveupdate-cli/main/install.sh | bash
```

Затем выполните команду в директории вашего проекта:

```bash
liveupdate-cli
```

Это автоматически:

- Соберет React Native или Capacitor приложение
- Создаст ZIP архив с обновлением
- Загрузит его на ваш сервер
- Сгенерирует необходимые метаданные

#### CodePush API

- `GET/POST /v0.1/public/codepush/update_check` - проверка обновлений
- `POST /v0.1/public/codepush/report_status/deploy` - отчет о статусе развертывания
- `POST /v0.1/public/codepush/report_status/download` - отчет о статусе загрузки

#### Capgo API

- `GET/POST /capgo/update_check` - проверка обновлений

## Структура файлов

```
codepush-php-server/
├── app/
│   ├── Capgo.php      # Обработчик Capgo API
│   └── CodePush.php   # Обработчик CodePush API
├── storage/           # Директория для хранения сборок
│   ├── capgo/         # Capgo сборки
│   └── codepush/      # CodePush сборки
├── vendor/            # Зависимости Composer
├── composer.json      # Конфигурация Composer
├── index.php          # Точка входа приложения
└── README.md          # Этот файл
```

## Требования

- PHP 8.0.2 или выше
- Composer
- Веб-сервер (Apache/Nginx)

## Безопасность

⚠️ **Важно:** Обязательно измените `upload_key` в файле `index.php` перед использованием в продакшене. Использование ключа по умолчанию приведет к ошибке 403.

## Лицензия

Этот проект распространяется под лицензией MIT.

## Поддержка

Если у вас есть вопросы или проблемы, создайте issue в репозитории проекта.
