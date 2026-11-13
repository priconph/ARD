@php
    $formatted = !empty($data->deadline_of_submission) 
    ? date('F j, Y', strtotime($data->deadline_of_submission)) 
    : 'N/A';
    // $formatted = $data->deadline_for_submission;
    $type = "{$auditResultType} Audit";
    $reportCpar = "";

    if($data->classification == 'CPAR'){
        $reportCpar = "Report and CPAR";

    }
    else{
        $reportCpar = "Report";
    }

@endphp
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<style type="text/css">
		body {
			font-family: Arial;
		}

		p, a{
			font-size: 14;
		}
	</style>
</head>
<body>
    <div>
        <label style="font-size: 18px;">Good day,</label>
        <br><br>
        <label style="font-size: 14px;">
            Please submit improvement actions for the attached {{ strtoupper($type) }} {{ $reportCpar }} to the QA Section on or before {{ $formatted }} for consolidation and review prior to uploading and monitoring.
            <br>
            <br>
            Kindly include root cause, corrective actions, target completion date, and supporting evidence, as applicable.
        </label>
        <br><br>
        <label style="font-size: 14px;">Thank you.</label>
    </div>
	{{-- Email Footer --}}
    <hr>
    <br>
    {{-- <div class="col-sm-12">
        <div class="form-group row">
            <label class="col-sm-12 col-form-label">For more info, please log-in to your Rapidx account. Go to http://rapidx/ and RFQv4 </label>
        </div>
    </div> --}}

    <br>
    <br>

    <div class="col-sm-12">
        <div class="form-group row">
            <label class="col-sm-12 col-form-label"><b> Notice of Disclaimer: </b></label>
            <br>
            <label class="col-sm-12 col-form-label"> <i> This message contains confidential information intended for a specific individual and purpose. If you are not the intended recipient, you should delete this message. Any disclosure,copying, or distribution of this message, or the taking of any action based on it, is strictly prohibited.</i></label>
        </div>
    </div>

    <div class="col-sm-12">
        <br><br>
        <label style="font-size: 18px;"><b>For concerns on using the form, please contact ISS at local numbers 205, 206, or 208.</b></label>
    </div>

</body>
</html>