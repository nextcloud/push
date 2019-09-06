# push

Nextcloud Push allows any Nextcloud App's back-end to communicate with the front-end, so that any process can create an event and broadcast it to a list of users' Nextcloud session.

Main purpose:
- Updating your app's UI in case of external updates without having to implement any polling.
- Displaying notifications/messages even if the user is not currently on your app.

 
### Installation

- Working only on this branch of Nextcloud: https://github.com/nextcloud/server/tree/stratos
- clone this repo and run `make`



### Configuration

_configure the type of polling: **short**, **long**, (nodejs not available yet);

``` ./occ config:app:set --value 'short' push type_polling```



_configure the delay (in seconds) to update the pooling:_

``` ./occ config:app:set --value '5' push delay_polling```

_display debug information in the web console of the Internet browser:_

``` ./occ config:app:set --value '1' push debug```



### Test

Check your setup by
 
 - open your Nextcloud in a browser
 - runs `./occ push:test`
 - within the next few second, a notification message should appears on your interface.


![](https://github.com/nextcloud/push/blob/master/documentations/PushTest.gif)


### Using Nextcloud Push from your app

Quickest way to use Nextcloud Push is to call its manager in the `__construct()` of your class.

```php
    use OCP\Push\IPushManager;

    /** @var IPushManager */
    private $pushManager;

    public function __construct(IPushManager $pushManager) {
        $this->pushManager = $pushManager;
    }
```



### Testing Push from your app

Just add those lines in your code and, when executed, a _Toast_ will be viewable by `userId` if currently browsing your Nextcloud
  
```php
    $pushHelper = $this->pushManager->getPushHelper();
    $pushHelper->test($userId);
```


### Sending a notification to a user _(or to a list of users)_

This is an example on how to create your custom notification. 
Please note also the commented line on how to add multiples users and groups. Even if a user belongs to multiple groups, 
```php
    $userId = 'cult';
    // $users = ['user1', 'user2'];
    // $group = 'admin';

    $notification = new \OC\Push\Model\Helper\PushNotification('push', IPushItem::TTL_INSTANT);
    $notification->setTitle('Testing Push');
    $notification->setLevel(PushNotification::LEVEL_SUCCESS);
    $notification->setMessage("If you cannot see this, it means it is not working.");
    $notification->addUser($userId);
    // $notification->addUsers($users);
    // $notification->addGroup($group);

    $pushHelper = $this->pushManager->getPushHelper();
    $pushHelper->pushNotification($notification);
```




### Broadcasting an event to the front-end of a user  _(or to a list of users)_

Events are only available for a couple of seconds. Meaning that if the user is not connected at the time of the event, it will not be broadcasted the next time the user is logged in.   
Events should only be used to update users' front-end.

```php
    $userId = 'cult';
    // $users = ['user1', 'user2'];
    // $group = 'admin';

    $event = new \OC\Push\Model\Helper\PushEvent('push', 'OCA.Push.test');
    $event->setPayload(
        [
            'message' => 'OCA.Push.test() was executed flawlessly'
        ]);
    $event->addUser($userId);
    // $event->addUsers($users);
    // $event->addGroup($group);

    $pushHelper = $this->pushManager->getPushHelper();
    $pushHelper->broadcastEvent($event);
```


### Using a callback

You can also define a callback on your front-end to which items will be broadcasted.
While adding your callback, you can set a limit to the `appId` or the `source` of the broadcast.


```javascript
if (OCA.Push && OCA.Push.isEnabled()) {
    // will receive all items broadcasted to the front-end
	OCA.Push.addCallback(this.myCallback)

    // will receive only items with appId set to 'push'
	//OCA.Push.addCallback(this.myCallback, 'push')

    // will receive all items with appId set to 'push' and
    // `source` set to 'testing'
	//OCA.Push.addCallback(this.myCallback, 'push', 'testing')
}
```

If you want to create an object that is only broadcast to callbacks, you can create a `PushCallBack`

```php
    $userId = 'cult';
    // $users = ['user1', 'user2'];
    // $group = 'admin';

    $item = new \OC\Push\Model\Helper\PushCallback('push', 'testing');
    $item->setPayload(
        [
            'info1' => 'This is info 1',
            'info2' => 42
        ]);
    $item->addUser($userId);
    // $item->addUsers($users);
    // $item->addGroup($group);

    $pushHelper = $this->pushManager->getPushHelper();
    $pushHelper->toCallback($item);
```



### Pushing your very own custom item






### yay, animation.

![](https://raw.githubusercontent.com/nextcloud/push/master/documentations/SocialPush.gif)



n=5

- si init du poll: 
* on recupere tous les published=0
* on recupere tous les published de moins de n secondes


- si pas init du poll:
* on affiche tout

rajouter un delay depuis le JS pour relancer le poll
