<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class EventVoter extends Voter
{
    public const EDIT = 'EVENT_EDIT';
    public const DELETE = 'EVENT_DELETE';
    public const VIEW = 'EVENT_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW])
            && $subject instanceof \App\Entity\Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($user->getRoles()[0] === 'ROLE_ADMIN') {
            return true;
        }

        if ($subject->getCreator() === $user) {
            return true;
        }
        else {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
//        switch ($attribute) {
//            case self::EDIT:
//                // logic to determine if the user can EDIT
//                // return true or false
//                break;
//
//            case self::VIEW:
//                // logic to determine if the user can VIEW
//                // return true or false
//                break;
//        }
//
//        return false;
    }
}
