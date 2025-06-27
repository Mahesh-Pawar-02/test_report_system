$(document).ready(function() {
    let partIndex = $('#partsTable tbody tr').length || 1;
    let paramIndex = $('#parametersTable tbody tr').length || 1;
    let traverseIndex = $('#traverseTable tbody tr').length || 1;

    $('#addPart').click(function() {
        let row = `
            <tr>
                <td><input type="text" name="parts[${partIndex}][part_no]" class="form-control"></td>
                <td><input type="number" name="parts[${partIndex}][quantity]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-part">Remove</button></td>
            </tr>`;
        $('#partsTable tbody').append(row);
        partIndex++;
    });

    $('#addParameter').click(function() {
        let row = `
            <tr>
                <td>
                    <select name="parameters[${paramIndex}][parameter]" class="form-control">
                        <option value="">Select Parameter</option>
                        <option value="Heat Treatment">Heat Treatment</option>
                        <option value="Surface Hardness">Surface Hardness</option>
                        <option value="Core hardness">Core hardness</option>
                        <option value="Core hardness @ RCD">Core hardness @ RCD</option>
                        <option value="Core hardness @ OD">Core hardness @ OD</option>
                        <option value="Case depth">Case depth</option>
                        <option value="Case depth @ PCD">Case depth @ PCD</option>
                        <option value="Case depth @ OD">Case depth @ OD</option>
                        <option value="Case depth @ Spline">Case depth @ Spline</option>
                        <option value="Case depth @ Ground">Case depth @ Ground</option>
                        <option value="Grain Size 100X">Grain Size 100X</option>
                        <option value="Grain Size">Grain Size</option>
                        <option value="Carbon Surface Content">Carbon Surface Content</option>
                        <option value="Root">Root</option>
                        <option value="Thread Hardness">Thread Hardness</option>
                        <option value="Microstructure">Microstructure</option>
                        <option value="Surface Hardness (After Grinding)">Surface Hardness (After Grinding)</option>
                    </select>
                </td>
                <td><input type="text" name="parameters[${paramIndex}][specified]" class="form-control"></td>
                <td><input type="text" name="parameters[${paramIndex}][actual]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-parameter">Remove</button></td>
            </tr>`;
        $('#parametersTable tbody').append(row);
        paramIndex++;
    });

    $('#addTraverse').click(function() {
        let row = `
            <tr>
                <td>
                    <select name="traverse[${traverseIndex}][location]" class="form-control">
                        <option value="">Select Location</option>
                        <option value="At PCD">At PCD</option>
                        <option value="At OD">At OD</option>
                        <option value="Root location">Root location</option>
                        <option value="Spline root">Spline root</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="traverse[${traverseIndex}][distance_mm]" class="form-control"></td>
                <td><input type="number" step="0.1" name="traverse[${traverseIndex}][hardness_hv1]" class="form-control"></td>
                <td><input type="number" step="0.1" name="traverse[${traverseIndex}][hardness_hrc]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-traverse">Remove</button></td>
            </tr>`;
        $('#traverseTable tbody').append(row);
        traverseIndex++;
    });

    $(document).on('click', '.remove-part', function() {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '.remove-parameter', function() {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '.remove-traverse', function() {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '.view-details', function() {
        let report_id = $(this).data('id');
        $.ajax({
            url: 'includes/functions.php',
            method: 'POST',
            data: { action: 'get_report_details', report_id: report_id },
            success: function(response) {
                $('#modalContent').html(response);
                $('#detailsModal').modal('show');
            },
            error: function() {
                alert('Error loading report details.');
            }
        });
    });
});