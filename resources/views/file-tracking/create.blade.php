@extends('layouts.app')
@section('css')
    <style type="text/css">
        .image-uplodasection {
            position: relative;
            margin-bottom: 10px;
        }

        .fileuploadicon {
            font-size: 24px;
        }



        .delete-img {
            position: absolute;
            top: 5px;
            right: 5px;
            cursor: pointer;
        }

        .preview-image {
            max-width: 100px;
            max-height: 100px;
            display: block;
            margin-top: 10px;
        }
    </style>
@endsection

@section('content')

    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
                <div class="row">
                    <div class="content-header-left col-md-6 col-6 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0">File Tracking</h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="{{ route('file-tracking.index') }}">Home</a>
                                        </li>
                                        <li class="breadcrumb-item active">Add New</li>


                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-header-right text-end col-md-6 col-6 mb-2 mb-sm-0">
                        <div class="form-group breadcrumb-right">
                            <a href="{{ route('file-tracking.index') }}"> <button class="btn btn-secondary btn-sm"><i
                                        data-feather="arrow-left-circle"></i> Back</button> </a>
                            <button class="btn btn-outline-primary btn-sm mb-50 mb-sm-0" type="button" id="save-draft-btn">
                                <i data-feather="save"></i> Save as Draft
                            </button>
                            <button type="submit" form="file-tracking-form" class="btn btn-primary btn-sm" id="submit-btn">
                                <i data-feather="check-circle"></i> Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">

                            <div class="card">
                                <form id="file-tracking-form" method="POST" action="{{ route('file-tracking.store') }}"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <input type ="hidden" name="book_code" id ="book_code_input">
                                    <input type="hidden" name="doc_number_type" id="doc_number_type">
                                    <input type="hidden" name="doc_reset_pattern" id="doc_reset_pattern">
                                    <input type="hidden" name="doc_prefix" id="doc_prefix">
                                    <input type="hidden" name="doc_suffix" id="doc_suffix">
                                    <input type="hidden" name="doc_no" id="doc_no">

                                    <div class="card-body customernewsection-form">


                                        <div class="border-bottom mb-2 pb-25">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="newheader ">
                                                        <h4 class="card-title text-theme">Basic Information</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>


                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Series <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select class="form-select" name="book_id" id="book_id" required>
                                                            <option value="" disabled {{ old('book_id') ? '' : 'selected' }}>Select</option>
                                                            @if($series)
                                                                @foreach ($series as $ser)
                                                                    <option value="{{ $ser->id }}" {{ old('book_id') == $ser->id ? 'selected' : '' }}>
                                                                        {{ $ser->book_code }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </div>


                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Doc No. <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="document_number" readonly
                                                            id="document_number" value="{{ old('document_number') }}">
                                                        <input type="hidden" name="document_status" id="document_status" value="">
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">File Name <span
                                                            class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">

                                                        <input type="text" class="form-control" name="file_name"
                                                            id="file_name" value="{{ old('file_name') }}" required>

                                                    </div>
                                                </div>



                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Upload Document <span
                                                            class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <input type="file" class="form-control" required name="file"
                                                            id="fileInput" accept=".pdf, .docx">

                                                        <div id="appenddata">
                                                            <!-- Preview images will be appended here -->
                                                        </div>
                                                    </div>
                                                </div>

                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Expected Completion Date <span
                                                            class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">

                                                        <input type="date" class="form-control" name="expected_completion_date"
                                                            id="expected_completion_date" value="{{ old('expected_completion_date') }}" min="{{ date('Y-m-d') }}">

                                                    </div>
                                                </div>



                                                <div class="row align-items-center mb-1 hidden">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Remarks</label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <textarea type="text" rows="4" class="form-control" placeholder="Enter Remarks here..." name="comments"
                                                            maxlength="250">{{ old('comments') }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Modal to add new record -->

                </section>
            </div>
        </div>
    </div>
    <!-- END: Content-->

@section('scripts')
    <script>
        $('#book_id').on('change', function() {
            let currentDate = new Date().toISOString().split('T')[0];
            let bookId = $('#book_id').val();
            let actionUrl = '{{ route('book.get.doc_no_and_parameters') }}' + '?book_id=' + bookId + "&document_date=" +
                currentDate;
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        $("#book_code_input").val(data.data.book_code);
                        if (!data.data.doc.document_number) {
                            $("#document_number").val('');
                            $('#doc_number_type').val('');
                            $('#doc_reset_pattern').val('');
                            $('#doc_prefix').val('');
                            $('#doc_suffix').val('');
                            $('#doc_no').val('');
                        } else {
                            $("#document_number").val(data.data.doc.document_number);
                            $('#doc_number_type').val(data.data.doc.type);
                            $('#doc_reset_pattern').val(data.data.doc.reset_pattern);
                            $('#doc_prefix').val(data.data.doc.prefix);
                            $('#doc_suffix').val(data.data.doc.suffix);
                            $('#doc_no').val(data.data.doc.doc_no);
                        }
                        if (data.data.doc.type == 'Manually') {
                            $("#document_number").attr('readonly', false);
                        } else {
                            $("#document_number").attr('readonly', true);
                        }

                    }
                    if (data.status == 404) {
                        $("#document_number").val('');
                        $('#doc_number_type').val('');
                        $('#doc_reset_pattern').val('');
                        $('#doc_prefix').val('');
                        $('#doc_suffix').val('');
                        $('#doc_no').val('');
                        alert(data.message);
                    }
                });
            });
        });
        if($('#book_id').val()!=null)
        $('#book_id').trigger('change');



        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
        })

        document.getElementById('save-draft-btn').addEventListener('click', function() {
            document.getElementById('document_status').value = 'draft';
            document.getElementById('file-tracking-form').submit();
        });

        document.getElementById('submit-btn').addEventListener('click', function() {
            document.getElementById('document_status').value = 'submitted';
        });

        let filesArray = [];

        // Maximum file size (5 MB in this example)
        const maxSize = 5 * 1024 * 1024;

        // Allowed file types
        const allowedExtensions = /(\.pdf|\.docx)$/i;

        document.getElementById('fileInput').addEventListener('change', function (event) {
    const inputElement = event.target; // File input element
    const files = Array.from(inputElement.files); // Convert FileList to Array

    // Filter and validate files
    const validFiles = files.filter(file => {
        if (file.size > maxSize) {
            alert('File ' + file.name + ' is too large. Maximum size is 5 MB.');
            return false;
        }
        if (!allowedExtensions.test(file.name)) {
            alert('Invalid file type for ' + file.name + '. Only PDF files are allowed.');
            return false;
        }
        return true;
    });

    if (validFiles.length < files.length) {
        // Some files are invalid; reset the input
        inputElement.value = ''; // Clear input to prevent invalid files from being submitted
    }

    // Add new valid files to the array
    filesArray = filesArray.concat(validFiles);
    updateFilePreviews();
    updateFileInput();
    feather.replace(); // Initialize Feather icons after updating the DOM
});

        function updateFilePreviews() {
            const appendData = document.getElementById('appenddata');
            appendData.innerHTML = ''; // Clear previous previews

            filesArray.forEach((file, index) => {
                const reader = new FileReader();

                // Create a div to hold the file preview
                const fileDiv = document.createElement('div');
                fileDiv.classList.add('image-uplodasection');

                // Create a preview element
                const filePreview = document.createElement('div');
                filePreview.classList.add('filepreview');

                // Create an icon or image for the file type
                const fileIcon = document.createElement('i');
                fileIcon.classList.add('fileuploadicon');

                if (file.type.startsWith('image/')) {
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('preview-image');
                        filePreview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Assign the correct icon for non-image files (e.g., PDF, DOCX)
                    let iconType;
                    switch (file.type) {
                        case 'application/pdf':
                            iconType = 'file-text'; // Or any other suitable icon
                            break;
                        case 'application/msword':
                        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                            iconType = 'file';
                            break;
                        default:
                            iconType = 'file';
                            break;
                    }
                    fileIcon.setAttribute('data-feather', iconType);
                    filePreview.appendChild(fileIcon);
                }

                // Add a delete button
                const deleteButton = document.createElement('div');
                deleteButton.classList.add('delete-img', 'text-danger');
                deleteButton.innerHTML = '<i data-feather="x"></i>';
                deleteButton.setAttribute('data-index', index); // Store the file index
                deleteButton.addEventListener('click', function() {
                    removeFile(this.getAttribute('data-index'));
                });
                filePreview.appendChild(deleteButton);


                fileDiv.appendChild(filePreview);
                appendData.innerHTML = ''; // Clear previous previews
                appendData.appendChild(fileDiv);
            });

            // Replace feather icons
            feather.replace();
        }


        function removeFile(index) {
            filesArray.splice(index, 1); // Remove the file from the array
            updateFilePreviews(); // Update the previews
            updateFileInput(); // Update the file input
        }

        function showToast(icon, title) {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                },
            });
            Toast.fire({
                icon,
                title
            });
        }

        @if (session('success'))
            showToast("success", "{{ session('success') }}");
        @endif

        @if (session('error'))
            showToast("error", "{{ session('error') }}");
        @endif

        @if ($errors->any())
            showTOast('error',
                "@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach"
            );
        @endif

        $(document).ready(function(){

        });
    </script>


@endsection
@endsection
