<?php

namespace App\Support;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class AdminFlashMessage
{
    public static function buildAlerts(?ViewErrorBag $errors = null): array
    {
        $alerts = [];
        $errors ??= session('errors');

        if ($success = session('success')) {
            $alerts[] = [
                'type' => 'success',
                'title' => session('success_title', self::titleForSuccess((string) $success)),
                'message' => $success,
            ];
        }

        if ($error = session('error')) {
            $alerts[] = [
                'type' => 'error',
                'title' => session('error_title', self::titleForError((string) $error)),
                'message' => $error,
            ];
        }

        $confirmReasonFields = ['rejection_reason', 'suspension_reason'];

        if ($errors instanceof ViewErrorBag && $errors->any()) {
            $bag = $errors->getBag('default');
            $confirmReasonErrorsOnly = collect($bag->keys())
                ->every(fn (string $key) => in_array($key, $confirmReasonFields, true));

            if (! $confirmReasonErrorsOnly) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => self::titleForValidation($bag),
                    'message' => self::messageForValidation($bag),
                ];
            }
        }

        return $alerts;
    }

    public static function titleForSuccess(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'signed out') => 'Signed out',
            str_contains($lower, 'created successfully') => 'Created successfully',
            str_contains($lower, 'updated successfully') => 'Updated successfully',
            str_contains($lower, 'deleted successfully') => 'Deleted successfully',
            str_contains($lower, 'approved') => 'Approved',
            str_contains($lower, 'rejected') => 'Rejected',
            str_contains($lower, 'suspended') => 'Suspended',
            str_contains($lower, 'inactivated') => 'Inactivated',
            str_contains($lower, 'activated') => 'Activated',
            str_contains($lower, 'reactivated') => 'Reactivated',
            default => 'Success',
        };
    }

    public static function titleForError(string $message): string
    {
        $lower = strtolower($message);

        return match (true) {
            str_contains($lower, 'profile page'),
            str_contains($lower, 'to suspend or block'),
            str_contains($lower, 'to suspend this'),
            str_contains($lower, 'to reject this'),
            str_contains($lower, 'suspend or block'),
            str_contains($lower, 'reject button') => 'Use the profile page',
            str_starts_with($lower, 'cannot delete'),
            str_starts_with($lower, 'cannot ') => 'Action not allowed',
            str_contains($lower, 'invalid username'),
            str_contains($lower, 'invalid password'),
            str_contains($lower, 'username or password'),
            str_contains($lower, 'not active') => 'Sign in failed',
            str_contains($lower, 'no pending'),
            str_contains($lower, 'select at least one') => 'Nothing selected',
            str_contains($lower, 'has orders on record'),
            str_contains($lower, 'has assigned orders'),
            str_contains($lower, 'cannot be deleted') => 'Cannot delete',
            str_contains($lower, 'is linked to'),
            str_contains($lower, 'is assigned to') => 'Action not allowed',
            str_contains($lower, 'already resolved'),
            str_contains($lower, 'already closed'),
            str_contains($lower, 'chat is closed') => 'Not available',
            str_contains($lower, 'cannot delete your own') => 'Action not allowed',
            str_contains($lower, 'super admin') => 'Action not allowed',
            default => 'Unable to complete',
        };
    }

    public static function titleForValidation(MessageBag $errors): string
    {
        return $errors->count() === 1
            ? 'Please fix the error below'
            : 'Please fix the errors below';
    }

    public static function messageForValidation(MessageBag $errors): string
    {
        if ($errors->count() === 1) {
            return (string) $errors->first();
        }

        if ($errors->count() <= 5) {
            return $errors->all() === [] ? '' : implode(' ', $errors->all());
        }

        return 'There are '.$errors->count().' fields that need your attention. Review the form and try again.';
    }
}
