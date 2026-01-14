// ... (previous code for add)

if ($action == 'edit') {
    $id = $_POST['id'] ?? 0;

    // Begin transaction
    $db->beginTransaction();

    try {
        // Update employees table
        $stmt = $db->prepare("
            UPDATE employees SET
                full_name = :full_name,
                gender = :gender,
                date_of_birth = :date_of_birth,
                blood_group = :blood_group,
                marital_status = :marital_status,
                nationality = :nationality,
                present_address = :present_address,
                mobile_number = :mobile_number,
                emergency_contact_name = :emergency_contact_name,
                emergency_contact_number = :emergency_contact_number,
                personal_email = :personal_email,
                permanent_address = :permanent_address,
                date_of_joining = :date_of_joining,
                employment_type = :employment_type,
                department_id = :department_id,
                designation_id = :designation_id,
                grade = :grade,
                reporting_manager_id = :reporting_manager_id,
                work_location = :work_location,
                shift_type = :shift_type,
                training_period = :training_period,
                probation_period = :probation_period,
                confirmation_date = :confirmation_date,
                commitment_from = :commitment_from,
                commitment_to = :commitment_to
            WHERE id = :id
        ");

        $stmt->execute([
            ':full_name' => $_POST['full_name'],
            ':gender' => $_POST['gender'],
            ':date_of_birth' => $_POST['date_of_birth'],
            ':blood_group' => $_POST['blood_group'],
            ':marital_status' => $_POST['marital_status'],
            ':nationality' => $_POST['nationality'],
            ':present_address' => $_POST['present_address'],
            ':mobile_number' => $_POST['mobile_number'],
            ':emergency_contact_name' => $_POST['emergency_contact_name'],
            ':emergency_contact_number' => $_POST['emergency_contact_number'],
            ':personal_email' => $_POST['personal_email'],
            ':permanent_address' => $_POST['permanent_address'],
            ':date_of_joining' => $_POST['date_of_joining'],
            ':employment_type' => $_POST['employment_type'],
            ':department_id' => $_POST['department_id'],
            ':designation_id' => $_POST['designation_id'],
            ':grade' => $_POST['grade'],
            ':reporting_manager_id' => $_POST['reporting_manager_id'] ?: null,
            ':work_location' => $_POST['work_location'],
            ':shift_type' => $_POST['shift_type'],
            ':training_period' => $_POST['training_period'] ?: null,
            ':probation_period' => $_POST['probation_period'] ?: null,
            ':confirmation_date' => $_POST['confirmation_date'] ?: null,
            ':commitment_from' => $_POST['commitment_from'] ?: null,
            ':commitment_to' => $_POST['commitment_to'] ?: null,
            ':id' => $id
        ]);

        // Update employee_kyc table
        $stmt = $db->prepare("
            UPDATE employee_kyc SET
                aadhaar_number = :aadhaar_number,
                pan_number = :pan_number,
                passport_number = :passport_number,
                passport_valid_from = :passport_valid_from,
                passport_valid_to = :passport_valid_to,
                driving_license_number = :driving_license_number,
                dl_valid_from = :dl_valid_from,
                dl_valid_to = :dl_valid_to,
                uan_number = :uan_number,
                pf_number = :pf_number,
                esic_number = :esic_number
            WHERE employee_id = :id
        ");

        $stmt->execute([
            ':aadhaar_number' => $_POST['aadhaar_number'],
            ':pan_number' => $_POST['pan_number'],
            ':passport_number' => $_POST['passport_number'] ?: null,
            ':passport_valid_from' => $_POST['passport_valid_from'] ?: null,
            ':passport_valid_to' => $_POST['passport_valid_to'] ?: null,
            ':driving_license_number' => $_POST['driving_license_number'] ?: null,
            ':dl_valid_from' => $_POST['dl_valid_from'] ?: null,
            ':dl_valid_to' => $_POST['dl_valid_to'] ?: null,
            ':uan_number' => $_POST['uan_number'] ?: null,
            ':pf_number' => $_POST['pf_number'] ?: null,
            ':esic_number' => $_POST['esic_number'] ?: null,
            ':id' => $id
        ]);

        $db->commit();

        // Redirect to employee list with success message
        header('Location: ../modules/employees/employees_list.php?msg=updated');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        // Redirect to employee edit page with error message
        header('Location: ../modules/employees/employee_edit.php?id=' . $id . '&msg=error&error=' . urlencode($e->getMessage()));
        exit;
    }
}

if ($action == 'delete') {
    $id = $_GET['id'] ?? 0;

    // Begin transaction
    $db->beginTransaction();

    try {
        // Delete from employee_kyc first (due to foreign key constraint)
        $stmt = $db->prepare("DELETE FROM employee_kyc WHERE employee_id = :id");
        $stmt->execute([':id' => $id]);

        // Then delete from employees
        $stmt = $db->prepare("DELETE FROM employees WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $db->commit();

        // Redirect to employee list with success message
        header('Location: ../modules/employees/employees_list.php?msg=deleted');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        // Redirect to employee list with error message
        header('Location: ../modules/employees/employees_list.php?msg=error&error=' . urlencode($e->getMessage()));
        exit;
    }
}