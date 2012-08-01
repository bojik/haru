Haru
====

Born in the spring

![Cat's return](https://github.com/TheRatG/haru/raw/0.3/docs/images/cats_return_cr.jpg)

## Описание

Универсальный автосборщик. Набор инструкций для [Phing](http://www.phing.info), предназначенный
для автоматизации сборки и установки проектов.

## Требования к ПО
PHP >= 5.3

## Установка
Скачайте и распакуйте архив необходимой версии программы [Haru](https://github.com/TheRatG/haru)  
Или  
Скопируйте репозиторий

    git clone git://github.com/TheRatG/haru.git

## Быстрый старт

Выполните следующие команды:

    git clone git://github.com/TheRatG/haru.git
    cd haru
    ./phing born -Dpath=/www/kin -Dhost=kin.dv -Dlib=kin -Dget-build.deploy.setup=1
    
**Учебный проект готов**  
Для запуска сайта настройте виртуальный хост: 

    ## создайте ссылку на кофигурационный файл для apache
    ## например
    sudo ln -s /www/kin/etc/kin.conf /usr/local/apache2/conf.d/kin.conf

## Функциональные возможности
Выделяются две группы функций: операции (targets), выполняемые непосредственно в папке haru (in haru), и
операции, выполняемые в папке build проекта (in project build). 
Так как проект использует Phing, то соответственно Haru наследует все возможности родителя.

In Haru
* создание учебного проекта, основанного на miao
* создание учебного проекта с инициализацией репозиториев
* установка проекта, при помощи project build

In Project Build
* установка проекта
* конфигуририрование проекта
* управление симлинками

## Основные понятия и жизненный цикл проекта

Под проектом (*project*) понимается совокупность проектных библиотек (*libs*), 
хранящиеся в репозитории и проектной сборки (*project_build*).

При установке проекта (*setup*) при помощи **Haru** (команда get-build) создается директория, в которую складывается
проектная сборка (*project_build*), и при помощи настроек этой сборки устанавливаются необходимые библиотеки,
конфигурируется проект.

Итого:
- **Haru** - главные инструкции по установке проекта
- **projeсt_build** - свойства проекта и дополнительные инструкции phing
- **project_lib** - проектная библиотека содержит необходимые директории и код, который хранится в репозитории
- **project** - "установленный" на платформе программный продукт
              
![Haru common](https://github.com/TheRatG/haru/raw/0.3/docs/images/haru_common.jpg)

### Циклы

#### Создание проекта
Создаем проект и сохраняем его в репозиторий для совместной разработки, возможно нам понадобится отменить правку.
Каждый разработчик работает со своей копией проекта, либо один и тот же человек за разными компьютерами.

#### Установка/выкат (deploy) проекта
При установке проекта мы указываем каталог хранения, помимо этого нам обычно необходимо изменить множество параметров
проекта (конфигурационные данные). Например: путь до исполняемого файла php, параметры соединения к БД, опции php.ini, 
имя тега/бранча lib.
Эти параметры зависят от типа платформы (на test одни параметры подлючения, на develop другие).
Помимо свойств зависящих от платформы имеется возможность расширить свойства пользовательским файлом. 
В частности, это очень полезно для develop платформы, когда над одним проектом работают несколько разработчиков.  

Установка новой версии продукта происходит так: 
- загружаются новые версии библиотек (lib) в отдельную директорию
- генерируются файлы из шаблонов
- переключаются symlink
- в случае, если нужно отменить выкат новой версии, переключаем symlink

![Haru change version](https://github.com/TheRatG/haru/raw/0.3/docs/images/haru_change_ver.jpg)

> **Вопрос:** Почему symlink, а не просто обновление репозитория?  
> **Ответ:** Так как помимо кода хранящегося в репозитории в папке библиотеки проекта 
> хранятся файлы сгенеренные из шаблонов. Также обновление может занимать длительное время, 
> переключение symlink работает быстрее.

#### Разработка
Разработчик имеет свою копию проекта. Настройки (конфигурационные данные) собственной копии расширяются 
в пользовательском файле `build/properties/extends/users/<username>.xml`, которые не сохраняются в репозиторий, 
так как содержит некоторые конфиденциальные данные.
Для разработки обычно создают отдельную ветку проекта (branch). После создания ветки, необходимо внести изменения 
в пользовательский файл настроек и переустановить проект.

## Свойства проекта
Свойства проекта хранятся в директории `build/properties` и имеют иерархическую структуру.

### Иерархия

*"Низы" меняются следом за "верхами"*

![Haru stages](https://github.com/TheRatG/haru/raw/0.3/docs/images/haru_stage.jpg)

#### Первая ступень 
Общие свойства `build/properties/config.xml`. Содержит системные настройки, 
например: корневая директория проекта, наименование виртуального хоста, путь к исполняемым файлам.
И файлы находящиеся в директории `build/properties/parts/*.xml`, сделаны для разделения файла `config.xml` на части 
для удобного редактирования.

> Файлы из директории `parts` используются для физического разделения ветвей xml, в отличии от других ступеней, где
> свойства заменяются.

```xml
<!-- пример build/properties/config.xml -->
<?xml version="1.0" encoding="UTF-8" ?>
<config>
	<project_name>kin</project_name>
	...	
	<paths>
		<root></root>

		<public>${config.paths.root}/public</public>
		<protected>${config.paths.root}/protected</protected>
		...
	</paths>
	<hosts>
		<root></root>
		<public>${config.hosts.root}</public>
		...
	</hosts>
	<system>
		<username></username>
		<bin>
			<php>php</php>
			...
		</bin>
	</system>
	<libs></libs>
</config>
```

```xml
<!-- пример build/properties/parts/miao.xml -->
<?xml version="1.0" encoding="UTF-8" ?>
<config>
	<libs>
		<Miao>
			<plugin>Standart</plugin>
			<deploy>
			...
</config>
```
#### Вторая ступень
Расширение настроек в зависимости от платформы.  
Файл выбирается на основании параметра bt (build type) при запуске
установки проекта. Может быть трех типов develop (D), test (T), production (P).
Пример запуска команды `./build/bin/phing install -Dbt=T`

Данные настройки имеет приоритет выше, чем у свойств первой ступени, соответственно параметры перекрываются второй ступенью.
Сюда складываются свойства типа: подлючения к БД, пользователь репозитория на test, production платформы.

#### Третья ступень
Пользовательские настройки. Данный файл, например `build/properties/extends/users/vpak.xml`, содержит личные настройки
и не сохраняется в репозитории.

Имеет высший приоритет перед остальными ступенями.

#### Для чего нужна иерархия?
Перекрытие свойств - удобный трюк для хранения параметров проекта. Он позволяет сократить количество параметров в файлах 
расширениях. Ступенчатость (разделение по директориям `extends`, `parts`) упорядочивает структуру файлов. Данный прием 
используется в Zend_Config и Ant.

В конце концов при установке проекта файлы расширений собираются в один большой конфигурационный
файл `build/tmp/configs/config.xml `, который используется в сборке проекта.

### Синтаксис
Файлы свойств имеют расширение xml, поэтому необходимо соблюдать правила форматирования xml.  
Помните, если свойство содержит спецсимволы, оборачивайте значение тега в **CDATA**.
Пример: `... <property><![CDATA[something with & "" ']]></property> ...`.

#### Обратные ссылки

Можно получить значение свойства по указанному пути. Переменная заключается в *${}*, путь разделяется *.*.

Пример.
```xml
<!-- Source -->
<config>
	<dir>/www/dir</dir>
	<file>file.xml</file>
	<fullname>${config.dir}/${config.file}</fullname>
	<!-- в качестве секции пути можно использовать переменную -->
	<example>
		<prop1>prop2</prop1>
		<prop2>value2</prop2>
		<prop3>${config.example.${config.example.prop1}}</prop3>
	</example>
</config>

<!-- Result -->
<config>
	<dir>/www/dir</dir>
	<file>file.xml</file>
	<fullname>/www/dir/file.xml</fullname>
	<!-- в качестве секции пути можно использовать переменную -->
	<example>
		<prop1>prop2</prop1>
		<prop2>value2</prop2>
		<prop3>value2</prop3>
	</example>
</config>
```

#### Атрибуты

### Секции

## In Haru

### Born

### Get Build

## In Project Build

### Install
### Configure
### Link