# Slack Receive

Slack receive port to D8
Original D7 version can be found here: https://www.drupal.org/project/slack_receive

Once the merge / co-maintenance issue is solved, this module will leave on drupal.org.   
Follow issue here: https://www.drupal.org/project/slack_receive/issues/3020373

# Usage
1. Create your Slack App and your Slash Command at https://api.slack.com/apps
2. Configure your slash command to POST at your-site.com/api/slash/command?_format=slash
3. Enable slack_receive module and at least one command responding sub-module (for instance slack_receive_example)
4. Register your Slack App at /admin/config/services/slack_receive
5. Start using your command in Slack :)

# Developer:
To respond to a new slash command, create a module and implement `hook_slack_receive_slash_command()`
For help, see `slack_receive.api.php` for hook documentation, and `slack_receive_example` module for a simple implementation.

NOTE: A much more advanced use case can use Views to populate a valid Slack Message. For instance, you could answer `/glossary #vocabulary term` to return the definition of a given taxonomy term using views.

To do so, build your view at `/admin/structure/views` as you would normally do, but use the provided _Slack Message_ display.
You can invoke that view in `hook_slack_receive_slash_command()` using:
```
// Retrieve the view results.
$view = Views::getView('your_view');
$view->setDisplay('a_slack_message_display');
$view->setExposedInput($args);
$view->execute();
$view_result = \Drupal::service('renderer')->renderRoot($view->render());
return Json::decode($view_result);
```
