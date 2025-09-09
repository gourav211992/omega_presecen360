@foreach($productionBomInstructions as $key => $bomInstruction)
@php
    $rowCount = $key + 1;
@endphp
<tr id="row_{{$rowCount}}" data-index="{{$rowCount}}">
    {{-- <td class="customernewsection-form">
       <div class="form-check form-check-primary custom-checkbox">
          <input type="checkbox" class="form-check-input" id="Email_{{$rowCount}}" value="{{$rowCount}}" data-id="{{$bomInstruction->id}}">
          <label class="form-check-label" for="Email_{{$rowCount}}"></label>
       </div>
    </td> --}}
    <td>
        <input disabled type="text" value="{{$bomInstruction?->station?->name}}" placeholder="Select" class="form-control mw-100 ledgerselecct" name="instruction_station" />
        <input type="hidden" value="{{$bomInstruction->station_id}}" name="instructions[{{$rowCount}}][station_id]">
           <input type="hidden" value="{{$bomInstruction?->station?->name}}" name="instructions[{{$rowCount}}][station_name]">
     </td>
     @if(isset($sectionRequired) && $sectionRequired)
    <td>
        <input disabled type="text" value="{{$bomInstruction?->section?->name}}" placeholder="Select" class="form-control mw-100 ledgerselecct" name="instruction_section" />
        <input type="hidden" value="{{$bomInstruction?->section_id}}" name="instructions[{{$rowCount}}][section_id]">
           <input type="hidden" value="{{$bomInstruction?->section?->name}}" name="instructions[{{$rowCount}}][section_name]">
     </td>
     @endif
     @if(isset($subSectionRequired) && $subSectionRequired)
    <td>
        <input disabled type="text" value="{{$bomInstruction?->subSection?->name}}" placeholder="Select" class="form-control mw-100 ledgerselecct" name="instruction_sub_section" />
        <input type="hidden" value="{{$bomInstruction?->sub_section_id}}" name="instructions[{{$rowCount}}][sub_section_id]">
           <input type="hidden" value="{{$bomInstruction?->subSection?->name}}" name="instructions[{{$rowCount}}][sub_section_name]">
     </td>
     @endif
    <td>
        <textarea disabled class="form-control mw-100" rows="1" name="instructions[{{$rowCount}}][instructions]">{!! $bomInstruction?->instructions !!}</textarea>
    </td>
    <td>
        <div class="d-flex justify-content-center align-items-center w-100">
           <input disabled type="file" name="instructions[{{$rowCount}}][attachment][]"  class="d-none"  id="file_input_{{$rowCount}}"  onchange="addFiles(this, 'instruction_file_preview_{{$rowCount}}')" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"> 
           <div class="d-flex align-items-center file-preview-container"  id="instruction_file_preview_{{$rowCount}}">
            @if($bomInstruction->getDocuments()?->count())
                <div class="row" id = "instruction_file_preview_{{$rowCount}}">
                    @foreach($bomInstruction->getDocuments() as $key => $attachment)
                    <div class="col-md-1 file-upload-preview" style="cursor: pointer;">
                        <div class="image-uplodasection expenseadd-sign">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text" onclick="previewFile(this);" file-url="{{$attachment->getDocumentUrl($attachment)}}"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
           </div>
        </div>
     </td>  
 </tr>
 @endforeach