@extends('layouts.app')
@section('title', 'Sửa học sinh')

@section('content')
@include('students.partials.form', [
    'action' => route('students.update', $student),
    'student' => $student,
    'primaryParent' => $student->parents->first(),
])

@include('students.partials.class-year-script')
@endsection
