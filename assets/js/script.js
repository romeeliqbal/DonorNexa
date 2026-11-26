
document.addEventListener("DOMContentLoaded", function () {

    const roleSelect = document.getElementById("role");
    const donorFields = document.getElementById("donor-fields");
    const hospitalFields = document.getElementById("hospital-fields");

    function updateFields() {

        const isDonor = roleSelect.value === "donor";
        const isHospital = roleSelect.value === "hospital";

        // Show and hide the correct sections
        donorFields.style.display = isDonor ? "block" : "none";
        hospitalFields.style.display = isHospital ? "block" : "none";

        // Enable or disable donor fields
        donorFields.querySelectorAll("input, select").forEach(function (field) {
            field.disabled = !isDonor;
        });

        // Enable or disable hospital fields
        hospitalFields.querySelectorAll("input, select").forEach(function (field) {
            field.disabled = !isHospital;
        });

    }

    // Listen for account type changes
    roleSelect.addEventListener("change", updateFields);

    // Set the correct fields when the page loads
    updateFields();

});
