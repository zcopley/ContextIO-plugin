
<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 *  Edit user settings for Context IO email account link
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Settings
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */
if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Edit user settings for Context IO email account link
 *
 * @category Settings
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 *
 * @see      SettingsAction
 */
class ContextiosettingsAction extends SettingsAction {

    private $flink;
    private $user;

    /**
     * For initializing members of the class.
     *
     * @param array $args misc. arguments
     *
     * @return boolean true
     */
    function prepare($args) {
        parent::prepare($args);

        // common_current_user gets the current user from the session
        $this->user = common_current_user();

        // see if we have a link for this user's email account already
        $this->flink = Foreign_link::getByUserID(
            $this->user->id,
            ContextIOPlugin::CONTEXTIO_SERVICE
        );

        return true;
    }

    /*
     * Check the sessions token and dispatch
     */
    function handlePost($args) {

        // CSRF protection
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(
                // TRANS: Client error displayed when the session token does not match or is not given.
                _m('There was a problem with your session token. Try again, please.')
            );
            return;
        }

        if ($this->arg('save')) {
            $this->saveSettings();
        } else if ($this->arg('connect')) {
            $this->connect();
        }
    }

    /**
     * Returns the page title
     *
     * @return string page title
     */
    function title() {
        // TRANS: title for ContextIO settings page
        return _m('TITLE', 'ContextIO settings');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */
    function getInstructions() {
        // TRANS: Instructions for ContextIO settings.
        return _m('ContextIO settings');
    }

    /*
     * Show the settings form if user has a link to ContextIO
     *
     * @return void
     */
    function showContent() {

        $form = new ContextIOConnectForm($this);

        if (!empty($this->flink)) {
            $form = new ContextIOConnectedForm($this);
        }

        $form->show();
    }

    /*
     * Save the setting
     *
     * @return void
     */
    function saveSettings() {

        $noticesync = $this->boolean('noticesync');

        $original = clone($this->flink);

        $this->flink->set_flags($noticesync, false, false, false);

        $result = $this->flink->update($original);

        // DB_DataObject returns false if it can't find anything
        if ($result === false) {
            // TRANS: Notice in case saving of preferences fail.
            $this->showForm(_m('There was a problem saving your preferences.'));
        } else {
            // TRANS: Confirmation that settings have been saved into the system.
            $this->showForm(_m('Preferences saved.'), true);
        }
    }

    /**
     * @return void
     */
    function connect()
    {
        $this->showForm(_m('Preferences saved.'), true);
    }
}

/*
 * Form for changing the setttings for ContextIO linked IMAP acct
 */
class ContextIOConnectedForm extends Form
{
    /**
     * Name of the form
     *
     * Sub-classes should overload this with the name of their form.
     *
     * @return void
     */

    function formLegend()
    {
        return _m('Connected IMAP email account settings');
    }

    /**
     * Visible or invisible data elements
     *
     * Display the form fields that make up the data of the form.
     * Sub-classes should overload this to show their data.
     *
     * @return void
     */
    function formData()
    {
        $this->elementStart('ul', 'form_data');

        $this->li();

        $flink = $this->out->flink;

        $this->checkbox(
            // We're pretty much abusing the noticesync bit flag... we only
            // ever envisioned sending notices and receiving notices. But
            // hey, I think this will work.
            'noticesync',
            _m('Search my inbox when adding attachments to a notice'),
            ($flink) ? ($flink->noticesync & FOREIGN_NOTICE_SEND) : true
        );

        $this->unli();

        $this->elementEnd('ul');
    }

    /**
     * Buttons for form actions
     *
     * Submit and cancel buttons (or whatever)
     * Sub-classes should overload this to show their own buttons.
     *
     * @return void
     */

    function formActions()
    {
        $this->out->submit('save', _m('BUTTON', 'Save'));
    }

    /**
     * ID of the form
     *
     * Should be unique on the page. Sub-classes should overload this
     * to show their own IDs.
     *
     * @return int ID of the form
     */

    function id()
    {
        return 'form_settings_contextio_connected';
    }

    /**
     * Action of the form.
     *
     * URL to post to. Should be overloaded by subclasses to give
     * somewhere to post to.
     *
     * @return string URL to post to
     */

    function action()
    {
        return common_local_url('contextiosettings');
    }

    /**
     * Class of the form. May include space-separated list of multiple classes.
     *
     * If 'ajax' is included, the form will automatically be submitted with
     * an 'ajax=1' parameter added, and the resulting form or error message
     * will replace the form after submission.
     *
     * It's up to you to make sure that the target action supports this!
     *
     * @return string the form's class
     */

    function formClass()
    {
        return 'form_settings';
    }
}

/**
 * Form for connecting a user's IMAP account via ContextIO
 */
class ContextIOConnectForm extends Form
{
    /**
     * Name of the form
     *
     * Sub-classes should overload this with the name of their form.
     *
     * @return void
     */

    function formLegend()
    {
        return _m('Connect your IMAP email account');
    }

    /**
     * Visible or invisible data elements
     *
     * Display the form fields that make up the data of the form.
     * Sub-classes should overload this to show their data.
     *
     * @return void
     */
    function formData()
    {
        $this->li();
        $this->out->element('p', null, 'Howdy!');
        $this->unli();
    }

    /**
     * Buttons for form actions
     *
     * Submit and cancel buttons (or whatever)
     * Sub-classes should overload this to show their own buttons.
     *
     * @return void
     */

    function formActions()
    {
        $this->out->submit('connect', _m('BUTTON', 'Connect'));
    }

    /**
     * ID of the form
     *
     * Should be unique on the page. Sub-classes should overload this
     * to show their own IDs.
     *
     * @return int ID of the form
     */

    function id()
    {
        return 'form_settings_contextio_connect';
    }

    /**
     * Action of the form.
     *
     * URL to post to. Should be overloaded by subclasses to give
     * somewhere to post to.
     *
     * @return string URL to post to
     */

    function action()
    {
        return common_local_url('contextiosettings');
    }

    /**
     * Class of the form. May include space-separated list of multiple classes.
     *
     * If 'ajax' is included, the form will automatically be submitted with
     * an 'ajax=1' parameter added, and the resulting form or error message
     * will replace the form after submission.
     *
     * It's up to you to make sure that the target action supports this!
     *
     * @return string the form's class
     */

    function formClass()
    {
        return 'form_settings';
    }
}
