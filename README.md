This is a Gremlin server client for PHP. **Supported versions : M9-RC3**

Changes
=======
There are many changes but bellow are the most noticeable if you've used rexpro-php before

- Client now throw errors that you will need to catch
- Connection params have changes
- Messages class has been revamped and is independant from Connection (see documentation on how to use this)
- Unit testing will require some more configuration
- Runs sessionless by default (rexpro-php 2.3 & 2.4+ ran with sessions as the default)
- Gremlin code change g.V/E should now be written as g.V()/E()


Installation
============

### PHP Gremlin-Server Client

##### For Gremlin-Server 3.0.0-M5+

Prefered method is through composer. Add the following to your **composer.json** file:

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/PommeVerte/gremlin-php.git"
        }
    ],
    "require": {
        "brightzone/gremlin-php": "master"
    }
}
```

If you just want to pull and use the library do:

```bash
git clone https://github.com/PommeVerte/gremlin-php.git
cd gremlin-php
composer install --no-dev # required to set autoload files
```

Namespace
=========

The Connection class exists within the `rexpro` namespace. (history: rexpro used to be the old protocol used by the driver in Tinkerpop2). This means that you have to do either of the two following:

```php
require_once('vendor/autoload.php');
use \brightzone\rexpro\Connection;

$db = new Connection;
```

Or

```php
require_once('vendor/autoload.php');

$db = new \brightzone\rexpro\Connection;
```

Examples
========

You can find more information by reading the API in the wiki.

Here are a few basic usages.

Example 1 :

```php
$db = new Connection;
//you can set $db->timeout = 0.5; if you wish
$db->open('localhost', 'graph');

$result = $db->send('g.V(2)');
//do something with result
$db->close();
```

Note that "graph" is the name of the graph configured in gremlin-server (not the reference to the traversal which is `g = graph.traversal()`)

Example 1 bis (Writing the same with message object) :
```php
$db = new Connection;
//you can set $db->timeout = 0.5; if you wish
$db->open('localhost', 'graph');

$db->message->gremlin = 'g.V(2)';
$result = $db->send(); //automatically fetches the message
//do something with result
$db->close();
```


Example 2 (with bindings) :

```php
$db = new Connection;
$db->open('localhost:8182', 'graph');

$db->message->bindValue('CUSTO_BINDING', 2);
$result = $db->send('g.V(CUSTO_BINDING)'); //mix between Example 1 and 1B
//do something with result
$db->close();
```

Example 3 (with session) :

```php
$db = new Connection;
$db->open('localhost:8182');
$db->send('cal = 5+5', 'session');
$result = $db->send('cal', 'session'); // result = [10]
//do something with result
$db->close();
```

Example 4 (transaction) :

```php
$db = new Connection;
$db->open('localhost:8182','graphT');

$db->transactionStart();

$db->send('n.addVertex("name","michael")');
$db->send('n.addVertex("name","john")');

$db->transactionStop(FALSE); //rollback changes. Set to true to commit.
$db->close();
```
Note that "graphT" above refers to a graph that supports transactions. And that transactions start a session automatically.

Example 5 (Using message object) :

```php
$message = new Messages;
$message->gremlin = 'g.V()';
$message->op = 'eval';
$message->processor = '';
$message->setArguments([
				'language' => 'gremlin-groovy',
				// .... etc
]);
$message->registerSerializer('\brightzone\rexpro\serializers\Json');

$db = new Connection;
$db->open();
$result = $db->send($message);
//do something with result
$db->close();
```
Of course you can affect the current db message in the same manner through $db->message.

Adding Serializers
==================

This library comes with a Json and an unused legacy Msgpack serializer. Any other serializer that implements SerializerInterface can be added dynamically with:

```php
$db = new Connection;
$serializer = $db->message->getSerializer() ; // returns an instance of the default JSON serializer
echo $serializer->getName(); // JSON
echo $serializer->getMimeType(); // application/json

$db->message->registerSerializer('namespace\to\my\CustomSerializer', TRUE); // sets this as default
$serializer = $db->message->getSerializer(); // returns an instance the CustomSerializer serializer (default)
$serializer = $db->message->getSerializer('application/json'); // returns an instance the JSON serializer
```
You can add many serializers in this fashion. When gremlin-server responds to your requests, gremlin-client-php will be capable of using the appropriate one to unserialize the message.

Unit testing
============

You will then need to run gremlin-server with the following configuration file : src/tests/gremlin-server-php.yaml

Just copy this file to `<gremlin-server-root-dir>/conf/`
And run the server using :

```bash
bin/gremlin-server.sh conf/gremlin-server-php.yaml
```

Note that you will not be able to test Transactions without a titan09-SNAPSHOT installation and custom configuration. Start an issue if you need more information.
