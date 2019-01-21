<?php

namespace Rebing\Timber\Requests\Contexts;

use Sentinel;

class UserContext extends AbstractContext
{
    protected $userContext;

    public function getData(): array
    {
        if ($this->userContext) {
            return $this->userContext;
        }

        if (Sentinel::check()) {
            $user = Sentinel::user();
            $data = [
                'id' => (string)Sentinel::id(),
            ];

            if (isset($user->name)) {
                $data['name'] = $user->name;
            }
            if (isset($user->email)) {
                $data['email'] = $user->email;
            }

            return ['user' => $data];
        }

        return [];
    }

    public function setUserContext(array $data): void
    {
        $this->userContext = $data;
    }
}