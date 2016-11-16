<?php namespace Sofa\Revisionable\Lumen;

use Sofa\Revisionable\Listener as ListenerInterface;
use Sofa\Revisionable\UserProvider;
use Sofa\Revisionable\Revisionable;
use Dingo\Api\Routing\Helpers;

class Listener implements ListenerInterface
{
    use Helpers;

    /**
     * User provider instance.
     *
     * @var mixed
     */
    protected $userProvider;

    /**
     * Create new listener.
     *
     * @param UserProvider $userProvider
     */
    public function __construct(UserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Handle created event.
     *
     * @param  \Sofa\Revisionable\Revisionable $revisioned
     * @return null
     */
    public function onCreated(Revisionable $revisioned)
    {
        $this->log('created', $revisioned);
    }

    /**
     * Handle updated event.
     *
     * @param  \Sofa\Revisionable\Revisionable $revisioned
     * @return null
     */
    public function onUpdated(Revisionable $revisioned)
    {
        if (count($revisioned->getDiff())) {
            $this->log('updated', $revisioned);
        }
    }

    /**
     * Handle deleted event.
     *
     * @param  \Sofa\Revisionable\Revisionable $revisioned
     * @return null
     */
    public function onDeleted(Revisionable $revisioned)
    {
        $this->log('deleted', $revisioned);
    }

    /**
     * Handle restored event.
     *
     * @param  \Sofa\Revisionable\Revisionable $revisioned
     * @return null
     */
    public function onRestored(Revisionable $revisioned)
    {
        $this->log('restored', $revisioned);
    }

    /**
     * Get currently logged in user.
     *
     * @return string|null
     */
    public function getCurrentUser()
    {
        $user=null;
        if($this->userProvider->getUser()!=null){
            $user =$this->userProvider->getUser();
        }else {
            $api = app(\Dingo\Api\Routing\Router::class);
            if ($api->getCurrentRequest()->method() != 'GET') {
                if (!empty($this->auth()->user())) {
                    $user = $this->auth()->user()->_id;
                }
            }
        }

        return $user;
    }

    /**
     * Log the revision.
     *
     * @param  string $action
     * @param  \Sofa\Revisionable\Revisionable $revisioned
     * @return null
     */
    protected function log($action, Revisionable $revisioned)
    {
        if (!$revisioned->isRevisioned()) {
            return;
        }

        $table = $revisioned->getTable();
        $id    = $revisioned->getKey();
        $user  = $this->getCurrentUser();
        $old   = [];
        $new   = [];
        $old_diff   = [];
        $new_diff   = [];

        switch ($action) {
            case 'created':
                $new = $revisioned->getNewAttributes();
                break;
            case 'deleted':
                $old = $revisioned->getOldAttributes();
                break;
            case 'updated':
                $old = $revisioned->getOldAttributes();
                $new = $revisioned->getNewAttributes();
                $new_diff=$revisioned->getDiff();
                foreach ($new_diff as $key => $nd){
                    if(isset($old[$key])) {
                        $old_diff[$key] = $old[$key];
                    }else{
                        $old_diff[$key]="";
                    }
                }
                break;
        }

        $logger = $revisioned->getRevisionableLogger();

        if ($connection = $revisioned->getRevisionableConnection()) {
            $logger->on($connection);
        }

        $logger->revisionLog($action, $table, $id, $old, $new, $user,$old_diff,$new_diff);
    }
}
