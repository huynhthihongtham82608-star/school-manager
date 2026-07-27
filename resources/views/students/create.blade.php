@extends('layouts.app')
@section('title', 'Thêm học sinh')

@section('content')
@include('students.partials.form', [
    'action' => route('students.store'),
    'student' => null,
    'primaryParent' => null,
])

@include('students.partials.class-year-script')
@endsection
