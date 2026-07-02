<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function createCitizen(User $user): bool
    {
        return $user->isCitizen() && $user->email_verified_at !== null;
    }

    public function viewInternal(User $user, Complaint $complaint): bool
    {
        return $user->isInternalUser() || (int) $complaint->submitted_by_user_id === (int) $user->id;
    }

    public function updateCitizen(User $user, Complaint $complaint): bool
    {
        return $complaint->canBeEditedByCitizen($user);
    }

    public function support(User $user, Complaint $complaint): bool
    {
        return $user->isCitizen()
            && $complaint->isPubliclyVisible()
            && (int) $complaint->submitted_by_user_id !== (int) $user->id;
    }

    public function comment(User $user, Complaint $complaint): bool
    {
        if ($user->isInternalUser()) {
            return true;
        }

        if ($user->isCitizen()) {
            return $complaint->isPubliclyVisible()
                || (int) $complaint->submitted_by_user_id === (int) $user->id;
        }

        return false;
    }

    public function confirmResolution(User $user, Complaint $complaint): bool
    {
        return $user->isCitizen()
            && !$complaint->is_anonymous_submission
            && $complaint->status === Complaint::STATUS_RESOLVED
            && (int) $complaint->submitted_by_user_id === (int) $user->id;
    }

    public function assignDepartment(User $user): bool
    {
        return $user->isAdmin() || $user->isMayor();
    }

    public function assignOfficer(User $user, Complaint $complaint): bool
    {
        if ($user->isAdmin() || $user->isMayor()) {
            return true;
        }

        return $user->isDepartmentHead() && $user->belongsToDepartment($complaint->assigned_department_id);
    }

    public function setPriority(User $user): bool
    {
        return $user->isAdmin();
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, Complaint $complaint): bool
    {
        if ($user->isAdmin() || $user->isMayor()) {
            return true;
        }

        if ($user->isDepartmentHead() && $user->belongsToDepartment($complaint->assigned_department_id)) {
            return true;
        }

        return $user->isActionOfficer() && (int) $complaint->assigned_officer_id === (int) $user->id;
    }

    public function addInternalNote(User $user, Complaint $complaint): bool
    {
        return $this->updateStatus($user, $complaint);
    }

    public function uploadAttachment(User $user, Complaint $complaint): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDepartmentHead()) {
            return $user->belongsToDepartment($complaint->assigned_department_id);
        }

        return $user->isActionOfficer() && (int) $complaint->assigned_officer_id === (int) $user->id;
    }

    public function downloadAttachment(User $user, Complaint $complaint): bool
    {
        return $this->uploadAttachment($user, $complaint);
    }

    public function override(User $user, Complaint $complaint): bool
    {
        return $user->isMayor() && !$complaint->isClosed();
    }

    public function viewAudit(User $user): bool
    {
        return $user->isAdmin() || $user->isMayor();
    }

    public function manageReferenceData(User $user): bool
    {
        return $user->isAdmin();
    }
}
