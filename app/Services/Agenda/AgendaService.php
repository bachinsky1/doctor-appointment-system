<?php

namespace App\Services\Agenda;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class AgendaService.
 */
class AgendaService
{
    public function __construct()
    {

    }

    /**
     * Summary of getAppointments
     *
     * @param int|null $user_id
     * @param int|null $month
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|\App\Models\Appointment[]
     */
    public function getAppointments($user_id = null, $month = null): Collection
    {
        if (!$user_id) {
            $user_id = Auth::id();
        }

        if (!$month) {
            $month = date('m');
        }

        $appointments = Appointment::with(['user', 'type', 'patient'])
            ->where(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->orWhere('patient_id', $user_id);
            })
            ->whereMonth('start', '=', $month)
            ->get();

        // If the current user is a patient, remove all appoinment titles except those created by the patient himself
        foreach ($appointments as $appointment) {
            if (Auth::user()->inRole('patient') && $appointment->patient_id != Auth::id()) {
                $appointment->title = '';
            }
            $appointment->patient = $appointment->patient_id == Auth::id() ? $appointment->user : $appointment->patient;
        }

        return $appointments;
    }


    /**
     * Summary of getAppointmentTypes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAppointmentTypes(): Collection
    {
        return AppointmentType::all();
    }

    /**
     * Summary of storeAppointment
     * @param \App\Http\Requests\StoreAppointmentRequest $request
     * @return bool
     */
    public function storeAppointment(StoreAppointmentRequest $request): bool
    {
        $user = Auth::user();

        $appointment = new Appointment;

        $appointment->user_id = $user->isA('healthcare') ? $user->id : $request->input('entity_id');
        $appointment->patient_id = $user->isA('patient') ? $user->id : $request->input('entity_id');

        $appointment->public_id = $request->input('public_id');
        $appointment->title = $request->input('title');
        $appointment->start = $request->input('start');
        $appointment->end = $request->input('end');
        $appointment->allDay = $request->input('allDay');
        $appointment->type_id = $request->input('type_id');

        return $appointment->save();
    }

    /**
     * Summary of destroyAppointment
     * @param mixed $public_id
     * @return bool|mixed|null
     */
    public function destroyAppointment($public_id): bool
    {
        $user = Auth::user();
        
        if (Auth::user()->inRole('patient')) {
            return Appointment::where('public_id', $public_id)
                ->where('patient_id', $user->id)
                ->delete();
        }

        if (Auth::user()->inRole('healthcare')) {
            return Appointment::where('public_id', $public_id)
                ->where('user_id', $user->id)
                ->delete();
        }

        return false;
    }


    public function searchPatient($search): Collection
    {
        $users = User::where('first_name', 'LIKE', '%' . $search . '%')
            ->orWhere('last_name', 'LIKE', '%' . $search . '%')
            ->get();

        return $users;
    }

}