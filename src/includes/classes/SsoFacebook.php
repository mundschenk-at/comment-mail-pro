<?php
/*[pro exclude-file-from="lite"]*/
/**
 * SSO for Facebook.
 *
 * @since     141111 First documented version.
 *
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license   GNU General Public License, version 3
 */
namespace WebSharks\CommentMail\Pro;

/**
 * SSO for Facebook.
 *
 * @since 141111 First documented version.
 */
class SsoFacebook extends SsoServiceBase
{
    /**
     * Class constructor.
     *
     * @since 141111 First documented version.
     *
     * @param array $request_args Incoming request args.
     */
    public function __construct(array $request_args)
    {
        parent::__construct('facebook', $request_args);
    }

    /**
     * Handle SSO authorization redirection.
     *
     * @since 141111 First documented version.
     */
    protected function maybeHandleAuthorize()
    {
        try { // Catch exceptions and log them for debugging.
            $service_factory = new \OAuth\ServiceFactory();
            $credentials     = new \OAuth\Common\Consumer\Credentials(
                $this->plugin->options['sso_'.$this->service.'_key'],
                $this->plugin->options['sso_'.$this->service.'_secret'],
                $this->plugin->utils_url->ssoActionUrl($this->service, 'callback')
            );
            /** @type $service \OAuth\OAuth2\Service\Facebook */
            $service = $service_factory->createService($this->service, $credentials, $this->storage, ['email']);

            $this->processAuthorizationRedirect($service->getAuthorizationUri());
        } catch (\exception $exception) {
            $this->processException($exception);
        }
    }

    /**
     * Handle SSO; i.e. account generation or login.
     *
     * @since 141111 First documented version.
     */
    protected function maybeHandleCallback()
    {
        try { // Catch exceptions and log them for debugging.
            if (!$this->request_args['code']) { // Must have this.
                throw new \exception('Missing oAuth code.');
            }
            $service_factory = new \OAuth\ServiceFactory();
            $credentials     = new \OAuth\Common\Consumer\Credentials(
                $this->plugin->options['sso_'.$this->service.'_key'],
                $this->plugin->options['sso_'.$this->service.'_secret'],
                $this->plugin->utils_url->ssoActionUrl($this->service, 'callback')
            );
            /** @type $service \OAuth\OAuth2\Service\Facebook */
            $service = $service_factory->createService($this->service, $credentials, $this->storage, ['email']);

            # Request access token via oAuth API provided by this service.

            $service->requestAccessToken($this->request_args['code']);

            # Acquire and validate data received from this service.

            if (!is_object($service_user = json_decode($service->request('/me')))) {
                throw new \exception('Failed to acquire user.');
            }
            if (empty($service_user->id) || !($sso_id = (string) $service_user->id)) {
                throw new \exception('Failed to obtain user.');
            }
            foreach (['first_name', 'last_name', 'name', 'email'] as $_prop) {
                if (!isset($service_user->{$_prop})) {
                    $service_user->{$_prop} = '';
                }
                if (strcasecmp($service_user->{$_prop}, 'private') === 0) {
                    $service_user->{$_prop} = ''; // If `private`; empty.
                }
            }
            unset($_prop); // Just a little housekeeping.

            if (!($fname = $this->request_args['fname'])) {
                $fname = $this->plugin->utils_string->firstName(
                    $this->coalesce($service_user->first_name, $service_user->name),
                    $this->coalesce($this->request_args['email'], $service_user->email)
                );
            }
            if (!($lname = $this->request_args['lname'])) {
                $lname = $service_user->last_name; // Try this next; obviously.
            }
            if (!$lname) {
                $lname = $this->plugin->utils_string->lastName($service_user->name);
            }
            $email = $this->coalesce($this->request_args['email'], $service_user->email);

            $this->processCallbackCompleteRedirect(compact('sso_id', 'fname', 'lname', 'email'));
        } catch (\exception $exception) {
            $this->processException($exception);
        }
    }
}
