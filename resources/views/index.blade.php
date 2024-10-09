@extends('layouts.main')

@section('title', 'Стартап')

@section('content')
<div class="d-flex justify-content-evenly m-5">
    <button class="btn btn-info"><a href="/evaluation-efficiency" class="text-decoration-none text-white">Оцінка ефективності стартап проекту</a></button>
    <button class="btn btn-info"><a href="/risk-assessment" class="text-decoration-none text-white">Оцінка ризиків стартап проекту</a></button>
</div>
@stop
