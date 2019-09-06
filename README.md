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

_need to write something here._



### Update an existing item

It is possible to create unique item that can be edited over time.

This is used in the Social App when a new account is following yours; the notification is identified by a **keyword** (here: _new_followers_).
Please note that the _Time To Live_ is set to `TTL_FEW_HOUR`
```php
    $userId = 'cult';

    $notification = new \OC\Push\Model\Helper\PushNotification('social', IPushItem::TTL_FEW_HOURS);
    $notification->setTitle('Nextcloud Social');
    $notification->setLevel(PushNotification::LEVEL_SUCCESS);
    $notification->setMessage('accountA is following you');
    $notification->addMetaArray('accounts', ['accountA']);
    $notification->setKeyword('new_followers');      // identification of the PushItem
    $notification->addUser($userId);

    $pushHelper = $this->pushManager->getPushHelper();
    $pushHelper->pushNotification($notification);
```


Now, before creating this notification, we should have checked that there is no already existing notifications about _new_followers_in the queue:
 


```php
$pushService = $this->pushManager->getPushService();
    try {
        $item = $pushService->getItemByKeyword('social', $userId, 'new_followers');
        // already exists, get the current item, edit its content and save the edited object
    } catch (ItemNotFoundException $e) {
        // does not exists, create a new one.
    }
```


If the precedent notification about _new_followers_ have been read by the recipient, we create a new one. If the recipient haven't read it, we can still edit the item:

```php
    $item->addMetaArray('accounts', 'accountB');
    $accounts = $item->getMeta('accounts');
    $newMessage = implode(', ', $accounts) . ' are now following you');

    $payload = $item->getPayload();
    $payload['message'] = $newMessage;
    $item->setPayload($payload);
    $pushService = $this->pushManager->getPushService();
    $pushService->update($item);
```

_Note about `push()` and `update()`_:
- `push()` will create a new item and erase any old item with the same keyword/userId/appId.
- `update()`will only update existing item. There is no confirmation if the item was already published before or not.




### Testing the _PushItem Update_ with the ./occ command

The `push:test` command comes with an option to register the testing notification using a _keyword_, 
with a long enough TTL, allowing an admin to edit the content of the testing notification before the recipient
of the notification open a session on the Nextcloud.

- Assuming the recipient's username on Nextcloud is _cult_
- Create the notification using the following command:

>     ./occ push:test --keyword new cult

- If the recipient is currently logged, a notification should be visible on Nextcloud
- If not, try the following command to change the text of the notification:

>     ./occ push:test --keyword 'An other message' cult

- If the recipient haven't seen the first notification yet, the next time a session is opened on Nextcloud the testing notification will be edited.



### yay, animation.

This is the Social App using the Push App to update its front-end when a new message is received.
![](https://raw.githubusercontent.com/nextcloud/push/master/documentations/SocialPush.gif)


