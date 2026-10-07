@extends('layouts.app')

@section('title', $isClinicStaff ? 'RAM-CIMS - Clinic Operations' : 'RAM-CIMS - My Dashboard')

@section('content')
    @if($isClinicStaff)
        @include('dashboard.staff')
    @else
        @include('dashboard.student')
    @endif
@endsection
