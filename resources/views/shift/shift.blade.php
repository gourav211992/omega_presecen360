@extends('layouts.app')
@section('content')

<form class="ajax-input-form" method="POST" action="{{ route('shift.store') }}" data-redirect="{{ url('/erp-shift') }}">
    @csrf
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
                <div class="row">
                    <div class="content-header-left col-md-6 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0">Production Shift</h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href = "#">Home</a></li>
                                        <li class="breadcrumb-item active">Add New</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                        <div class="form-group breadcrumb-right">
                           <a href="{{ route('shift.index') }}" class="btn btn-secondary btn-sm"><i data-feather="arrow-left-circle"></i> Back</a>
                            <button type="submit" class="btn btn-primary btn-sm mb-50 mb-sm-0">
                                <i data-feather="check-circle"></i> Create
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body customernewsection-form">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="newheader border-bottom mb-2 pb-25">
                                                <h4 class="card-title text-theme">Basic Information</h4>
                                                <p class="card-text">Fill the details</p>
                                            </div>
                                        </div>
                                     
                                        <div class="col-md-9">

                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Staff Name <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" name="staff_name" id="staff_name" class="form-control" placeholder="Enter Staff Name" value="{{isset($shift->name)?$shift->name:''}}" required>
                                                    <input type="hidden" name="staff_id" id="staff_id" class="form-control" placeholder="Enter Staff Name" value="{{isset($shift->id)?$shift->id:''}}" required>
                                                </div>
                                            </div> 
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="time" name="start_time" id="start_time" class="form-control" value="{{isset($shift->start_time)?$shift->start_time:''}}" required>
                                                </div>
                                            </div>

                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="time" name="end_time" id="end_time" class="form-control" value="{{isset($shift->end_time)?$shift->end_time:''}}" required>
                                                </div>
                                            </div>

                                           
                                        </div>
                                     <div class="col-md-3 border-start">
                                        <div class="row align-items-center mb-2">
                                                    <div class="col-md-12"> 
                                                        <label class="form-label text-primary"><strong>Status</strong></label>   
                                                        <div class="demo-inline-spacing">
                                                         @foreach ($status as $index => $option)
                                                            <div class="form-check form-check-primary mt-25">
                                                                <input 
                                                                    type="radio" 
                                                                    id="status_{{ strtolower($option) }}" 
                                                                    name="status" 
                                                                    value="{{ $option }}" 
                                                                    class="form-check-input"
                                                                    {{ (isset($shift->status) && $shift->status == $option) || (!isset($shift->status) && $index == 0) ? 'checked' : '' }}>
                                                                <label class="form-check-label fw-bolder" for="status_{{ strtolower($option) }}">
                                                                    {{ ucfirst($option) }}
                                                                </label>
                                                            </div>
                                                        @endforeach

                                                        </div>
                                                        @error('status')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div> 
                                            </div> 
                                        </div>

                            
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</form>
@endsection

