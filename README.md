# Lx - платформа для создания вэб-приложений

В данном репозитории находится ядро платформы. Одного его уже достаточно для создания вэб-приложений, но рекомендуем ознакомиться с прочими полезными репозиториями, содержащими документацию, примеры и инструменты для данной платформы:
* [lx-doc](https://github.com/epicoon/lx-doc)
* [lx-demo](https://github.com/epicoon/lx-demo)
* [lx-dev-wizard](https://github.com/epicoon/lx-dev-wizard)
* [lx-tools](https://github.com/epicoon/lx-tools)


## Оглавление
* [Характерные черты](#properties)
* [Установка](#deploy)
* [Описание архитектуры](#architecture)
* [CLI](#cli)
* [Пример разработки приложения](TODO)


<a name="properties"><h2>Характерные черты</h2></a>
* TODO Требует описания...


<a name="deploy"><h2>Установка</h2></a>
1. Для установки платформы воспользуйтесь менеджером php-пакетов `Composer`
   Пример файла `composer.json`:
   ```
   {
       "require":{
           "lx/lx-core":"dev-master"
       },
       "repositories":[
           {
               "type":"git",
               "url":"https://github.com/epicoon/lx-core"
           }
        ]
   }
   ```
   Чтобы использовать прочие lx-пакеты, просто добавьте их в конфигурационный файл по аналогии с пакетом `lx/lx-core`, например: [конфигурация с прочими lx-пакетами](https://github.com/epicoon/lx-doc-files/composer-example.md)
   В корне проекта выполните команду `composer install`
   В результате будет создан каталог `vendor` (если еще не существовал). В нем в папке `lx` будут располагаться указанные в зависимостях пакеты.
2. Настройка сервера для `nginx` под `Ubuntu`
   Конфигурация:
   ```
   server {
      charset utf-8;
      client_max_body_size 128M;
      listen 80;
      listen [::]:80;

      server_name server.name;
      root /path/to/project;
      index path/to/index.php;
	
      location / {
         try_files $uri /path/to/index.php?$args;
      }

      location ~ \.php$ {
         include fastcgi_params;
         fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
         fastcgi_pass unix:/run/php/php7.1-fpm.sock;
      }
   }
   ```
   Обратите внимание на пути и версию php-fpm, подставьте свои значения.
   Создайте запись в `/etc/hosts`
   Перезапустите сервер.
3. Чтобы развернуть платформу в проект, нужно запустить php-скрипт `vendor/lx/lx-core/lx-install`
   В результате в корне проекта будут созданы каталоги:
   * lx - каталог для конфигурационных и системных файлов платформы. Обязателен.
   * services - каталог для сервисов приложения (подробности ниже). Содержит первый сервис для приложения. Необязателен, работа с сервисами настраивается в конфигурации приложения.
4. Осталось вызвать запуск lx-приложения.
   Для этого в индексном файле нужно добавить код:
   ```php
   /* Пример приведен для ситуации, когда индексный файл находится в корне проекта
    * Если он находится в каталоге (например web), нужно скорректировать путь
    */
   require_once __DIR__ . '/vendor/lx/lx-core/main.php';
   lx::run();
   ```
5. Если в браузере по домену, указанному в конфигурации сервера и файле `/etc/hosts` вы видите страницу:
   ![Стартовая страница lx](https://github.com/epicoon/lx-doc-files/images/lx-start-page.png)
   то все удалось.


<a name="architecture"><h2>Описание архитектуры</h2></a>
Основные элементы приложения, из которых складывается вся архитектура:
* [Пакеты](#arch-package)
* [Сервисы](#arch-service)
* [Роутер уровня приложения](#arch-router)
* [Роутеры уровня сервисов](#arch-service-router)
* [Контроллеры](#arch-controller)
* [Действия (экшены)](#arch-action)
* [Модули](#arch-module)
* [Блоки](#arch-block)
* [Виджеты](#arch-widget)
* [Респонденты](#arch-respondent)

* <a name="arch-package"><h3>Пакеты</h3></a>
  Приложение состоит из пакетов.
  Пакет это каталог, имеющий особый конфигурационный файл. Варианты названий конфигурационного файла:
  * `composer.json`
  * `lx-config.php`
  * `lx-config.yaml`
  * `lx-config/main.php`
  * `lx-config/main.yaml`
  Наличие конфигурационного файла `composer.json` означает, что каталог является composer-пакетом.
  Наличие конфигурационного файла с префиксом `lx` означает, что каталог является lx-пакетом.
  Пакет может быть composer-пакетом и lx-пакетом одновременно (иметь оба конфигурационных файла).
  В файле lx-конфигурации рекомендуется описывать правила автозагрузки (а не в `composer.json`), т.к. платформа имеет свой автозагрузчик, не противоречащий автозагрузчику композера, но имеющий расширенные возможности.
  Файл `composer.json` может быть использован для описания зависимостей.
  О прочих особенностях lx-конфигурации далее.
  Пакеты могут располагаться в нескольких каталогах внутри приложения. В каких именно - определяется в конфигурации приложения. [Подробнее о конфигурации приложения](TODO)

* <a name="arch-service"><h3>Сервисы</h3></a>
  Сервис это пакет, умеющий отвечать на запросы. Имеет специальное поле с настройками в lx-конфигурации. Пример конфигурации на `yaml`:
  ```yaml
  # Имя сервиса
  name: lx/lx-dev-wizard

  # Правила автозагрузки
  autoload:
    psr-4:
      lx\devWizard\: ''

  # Поле с настройками сервиса - наличие именно этого поля превращает пакет в сервис
  service:
    # Если сервис представлен собственным классом, здесь указывается его имя
    class: lx\devWizard\Service

    # Прочие настройки сервиса
    modules: module
    models: model
    ...
  ```
  Имеет определенную инфраструктуру, описываемую с помощью lx-конфигурации.
  [Подробнее о конфигурации сервиса](TODO)

* <a name="arch-router"><h3>Роутер уровня приложения</h3></a>
  Запросы приложение распределяет по сервисам с помощью роутера. Он существует в приложении в единственном числе.
  Настраивается роутер в конфигурации приложения. Пример:
  ```yaml
  ...

  # Варианты типов:
  # - map 
  #   необходим параметр 'routes' - сама карта, либо 'path' - где лежит карта
  #   карта - массив, где ключ - URL запроса (или регулярное выражение, если начинается с символа '~'),
  #   значение - данные от объекте, куда перенаправляется запрос
  # - class 
  #   необходим параметр 'name' - имя класса роутера
  #   роутер наследуется от [[lx\Router]] и должен реализовать метод [[route()]],
  #   который будет возвращать данные от объекте, куда перенаправляется запрос
  router:
    type: map
    routes:
      # Домашняя страница
      /: your/home-service
      # URL, ориентированный сразу на модуль сервиса, причем работающий только для определенного режима приложения
      test-page: {service-module: 'your/some-service:some-module', on-mode: dev}

  ...
  ```
  ![Схема архитектуры](https://github.com/epicoon/lx-doc-files/images/architecture-scheme.png)
  [Подробнее о роутинге](TODO)

* <a name="arch-service-router"><h3>Роутеры уровня сервиса</h3></a>
  Управлением запросами внутри сервисов занимаются роутеры сервисов.
  По аналогии с роутером приложения, роутер сервиса можно настройить с помощью файла lx-конфигурации сервиса, либо переопределить класс `lx.ServiceRouter`.
  Пример настройки через файл lx-конфигурации:
  ```yaml
  name: lx/lx-dev-wizard
  ...

  service:
    class: lx\devWizard\Service

    # Настройки роутера сервиса
    router:
      type: map
      routes:
        # Направление запроса на контроллер. Будет возвращен результат вызова метода(экшена) контроллера [[run()]]
        some-route-1: ControllerClassName
        # Направление запроса на контроллер. Будет возвращен результат вызова метода(экшена) контроллера [[actionName()]]
        some-route-2: ControllerClassName::actionName
        # Направление запроса на экшен. Будет возвращен результат вызова метода экшена [[run()]]
        some-route-3: {action: ActionClassName}
        # Направление запроса на модуль. Будет возвращен результат рендеринга модуля
        some-route-4: {module: moduleName}

        web-cli: {module:webcli}
    ...
  ```
  ![Схема внутрисервисного роутинга](https://github.com/epicoon/lx-doc-files/images/service-routing.png)
  [Подробнее о внутрисервисном роутинге](TODO)

* <a name="arch-controller"><h3>Контроллеры</h3></a>
  Контроллер является таким элементом сервиса, который отвечает на запросы и может обрабатывать много разных URL.
  Если в настройках роутера уровня сервиса для определенного URL указано только имя класса контроллера, для обработки запроса будет вызван метод [[run()]].
  Если в настройках роутера уровня сервиса для определенного URL указано имя класса контроллера и метод (н-р так: ControllerClassName::actionName), то для обработки запроса будет вызван указанный метод.

* <a name="arch-action"><h3>Действия (экшены)</h3></a>
  Действие(экшен) является таким элементом сервиса, который отвечает на какой-то один запрос.
  Для обработки запроса будет вызван метод [[run()]].

* <a name="arch-module"><h3>Модули</h3></a>
  Модуль является элементом сервиса, представляющим собой совокупность логики, выполняющейся на клиенте и графического интерфейса, ее обслуживающего. По своей идее напоминает SPA.
  
  Свойства модуля:
  * рендерится и загружается браузером однократно
  * выполняется без перезагрузки страницы
  * имеет свою директорию с определенной инфраструктурой
  * имеет свой файл lx-конфигурации для настройки некоторых параметров и инфраструктуры
  * имеет свои ресурсы (js-код, css-код, изображения и т.п.)
  * данные с сервера запрашивает при помощи ajax-запросов
  * для формирования данных, отдаваемых сервером имеет свои инструменты (респонденты)
  * любой модуль может загрузить любой другой модуль и поместить его в элемент на своей странице
  * рендеринг модуля можно инициировать с передачей параметров, с случае, если они предусмотрены
  
  Перечень элементов инфраструктуры модуля:
  * Js-код модуля. Для него предусмотрена отдельная директория (ключ конфигурации `frontend`). Корневых файла для исполнения два - один будет выполняться до разворачивания модуля (ключ конфигурации `jsMain`), другой после (ключ конфигурации `jsBoorstrap`).
  * Респонденты. Классы, которые пишутся на php, представляют собой ajax-контроллеры, отдающие данные клиентской части модуля (ключ конфигурации `respondents`).
  * Представление. Для него предусмотрена отдельная директория (ключ конфигурации `view`). Корневой файл для рендеринга представления содержит код с описанием графического интерфейса (ключ конфигурации `viewIndex`).
  * Изображения (ключ конфигурации `images`). Можно задать каталог, в котором будут лежать изображения модуля.
  * Css-ресурсы (ключ конфигурации `css`). Можно задать каталог, в котором будут лежать css-файлы модуля. При загрузке модуля эти файлы будут использоваться автоматически.
  Пример настройки элементов инфраструктуры модуля в lx-конфигурации:
  ```yaml
  # В корне модуля каталог 'frontend' будет содержать js-код
  frontend: frontend
  # В каталоге 'frontend' js-код, который выполнится до разворачивания модуля будет находиться в файле '_bootstrap.js'
  jsBootstrap: _bootstrap.js
  # В каталоге 'frontend' js-код, который выполнится после разворачивания модуля будет находиться в файле '_main.js'
  jsMain: _main.js

  # Карта респондентов
  respondents:
    # Ключ - псевдоним респондента для клиентской стороны
    # Значение - имя класса респондента. Пространство имен указывается относительно пространства имен модуля(!)
    Respondent: backend\Respondent

  # В корне модуля каталог 'view' будет содержать код графического интерфейса
  view: view
  # В каталоге 'view' корневым файлом графического интерфейса будет '_root.php', он содержит код корневого блока модуля
  viewIndex: _root.php

  # Путь к директории с изображениями
  # Можно использовать алиасы приложения - тогда путь будет построен согласно алиасу
  # Можно начать с символа '/' - тогда путь будет считаться относительно корня сайта
  images: assets/images

  # Путь к директории с css-файлами
  css: assets/css
  ```
  Нижнее подчеркивание в названиях файлов - выбор автора платформы для обозначения особого статуса файлов (корневые файлы, точки входа для выполнения и т.п.), а также упрощение их визуального поиска в проводнике проекта при сортировке каталогов и файлов по алфавиту (нижнее подчеркивание находится между прописными и строчными символами).

* <a name="arch-block"><h3>Блоки</h3></a>
  Блоки это обособленные фрагменты графического интерфейса. Могут быть представлены либо отдельным php-файлом, либо каталогом, в котором должен присутствовать php-файл с представлением (в таком случае, имя php-файла должно соответствовать имени каталога блока, при этом возможно, но не обязательно предварение имени файла нижним подчеркиванием).

  Блок может иметь свой js-код, управляющий описываемым фрагментом графического интерфейса (как раз для этого нужен вариант блока-каталога). В этом случае главный исполняемый js-файл должен иметь такое же имя, как файл представления блока (но с расширением `.js` вместо `.php`). При выполнении js-кода в браузере, доступно пространство имен родительского блока, но недоступно пространство имен вложенных блоков. Например, в блок `A` вложен блок `B`, в блок `B` вложен блок `C`: `A -> B -> C`. В блоках объявлены соответственно переменные `a`, `b` и `c`. Тогда в блоке `A` будет доступна только переменная `a`. В блоке `B` будут доступны переменные `a` и `b`. В блоке `C` будут доступны переменные `a`, `b` и `c`.

  Корневой блок модуля находится в каталоге представления. Имена каталога представления и файла корневого блока задаются в lx-конфигурации модуля.

  При написании кода блока доступны две контекстные переменные:
  * $Module - объект модуля, к которому относится блок
  * $Block - объект самого блока, с которым можно работать как с обычным виджетом класса `lx\Box`

  Блок строится из виджетов, в некоторые виджеты можно вкладывать блоки. Блоки можно добавлять непосредственно в описываемый блок. Пример:
  ```php
  ...
  // Создаются виджеты
  $menu = new lx\Box($menuConfig);
  // Виджет добавляется внутрь другого виджета
  $button = $menu->add(lx\Button::class, $buttonConfig);

  // Добавим пару виджетов, чтобы вложить в них блоки
  $box1 = new lx\Box($config1);
  $box2 = new lx\Box($config2);

  // Вкладываем блок - указываем путь к файлу (без расширения) или каталогу блока
  // В данном случае путь относительно файла описываемого в данный момент блока
  // Можно использовать алиасы приложения
  // Если путь начать с '/', то он считается относительно корня сайта
  $box1->setBlock('blockName1');

  // Вкладываем еще один блок - указываем конфигурацию
  // @param path - тот же путь, что в предыдущем случае
  // @param renderParams - массив параметров, которые будут доступны в качестве переменных в файле, описывающем вкладываемый блок
  // @param clientParams - массив параметров, которые будут доступны в js-коде блока на стороне клиента
  $box2->setBlock([
    'path' => 'blockName2',
    'renderParams' => [],
    'clientParams' => [],
  ]);
  ...
  ```

* <a name="arch-widget"><h3>Виджеты</h3></a>
  TODO Требует описания...

* <a name="arch-respondent"><h3>Респонденты</h3></a>
  Респонденты это функциональные элементы модуля. Фактически являются php классами. Представляют собой ajax-контроллеры, которые отдают данные клиентской части модуля.
  Пример респондента:
  * Определение в конфигурации модуля
    ```yaml
    respondents:
      Respondent: backend\Respondent
    ```
  * Код респондента (должен находиться в файле `backend/Respondent.php` относительно корня модуля)
    ```php
    <?php

    namespace path\to\module\backend;

    class Respondent extends \lx\Respondent
    {
      public function test()
      {
        return 'Hello from server';
      }
    }
    ```
    * Использование респондента в js-коде модуля
    ```js
    ^Respondent.test() : (result) => {
      // result содержит строку 'Hello from server'
      console.log(result);
    };
    ```


<a name="cli"><h2>CLI</h2></a>
Приложение поддерживает интерфейс командной строки.
Чтобы его запустить нужно перейти в директорию `path\to\project\lx` и выполнить команду `php lx cli`.
Команда `\h` (или `help`) отобразит список доступных команд.

Создадим свой сервис. Для этого введем команду `\cs` (или `create-service`).
Нам предложат ввести имя сервиса. Введем что-то вроде `i-am-vendor/my-service`.
Так как в конфигурации приложения указано несколько директорий для пакетов (и сервисов в частности), нам предложат выбрать нужную директорию. Выберем вторую (services) - вводим `2`.
Готово!
По указанному адресу можно проверить что именно создалось, сверить с описанной в этой документации инфраструктурой сервиса.

Теперь создадим в сервисе модуль. Для этого перейдем в сервис. Сделать это можно несколькими путями, например введем команду `\g i-am-vendor/my-service` (если назвали сервис по-своему - используйте свое название). Признаком того, что мы перешли в сервис является смена `lx-cli<app>` на `lx-cli<service:i-am-vendor/my-service>`. Другой способ попасть в сервис - по индексу. Узнать индекс можно командой `\sl` - будет отображен список имеющихся сервисов, порядковый номер сервиса и есть его индекс. Например, если индекс 2, то перейти к сервису можно командой `\g -i=2`.
Наконец создаем модуль командой `\cm`. Нас снова попросяв ввести имя, вводим, например, `myModule`.
Это все!
Модуль создан, по указанному адресу можно проверить что именно создалось, сверить с описанной в этой документации инфраструктурой модуля.

Теперь можно узнать подробнее как разрабатывать свое приложение по [ссылке](TODO).
