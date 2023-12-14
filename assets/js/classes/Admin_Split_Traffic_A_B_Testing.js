export default class Admin_Split_Traffic_A_B_Testing {

    constructor() {
        this.submitForm()
        this.rangeOnChange()
    }

    /**
     * submitForm function is used for submitting data from plugin page, with this plugin we assure not to refresh inside of dashboard
    */

    submitForm = (event) => {
        document.querySelector('#submit_new_values_for_expiry').addEventListener('click', (event) => {

            // Prevent the default form submission
            event.preventDefault();

            let url = event.currentTarget.closest('form').action
            let amount_for_unique_expiry = document.querySelector('#amount_for_unique_expiry').value
            let unit_for_unique_expiry = document.querySelector('#unit_for_unique_expiry').value
            let admin_form_subbmision_nonce = document.querySelector('#expiry_form').elements['admin_form_subbmision_nonce'].value

           
            let loadingDialog = document.querySelector('#loadingDialog')

            loadingDialog.showModal()


            let data = new URLSearchParams({
                admin_form_subbmision_nonce,
                'action': 'admin_form_subbmision',
                amount_for_unique_expiry,
                unit_for_unique_expiry,
            })

            loadingDialog.showModal()

            fetch(url, {
                method: 'POST',
                body: data,
            }).then(
                response => response.text()
            ).then(data => {
                console.log(data)
                loadingDialog.close()
            }).catch((error) => {
                console.error('Error:', error)
            }).finally(() => {
                loadingDialog.close()
            })

        })


    }

    /**
     * rangeOnChange function is used for listening range changes so number can be displayed to user
    */

    rangeOnChange = () => {

        let rangeInput = document.querySelector("#amount_for_unique_expiry");

        // Add an event listener for the input event
        rangeInput.addEventListener("input", (e) => {

            document.querySelector("#value_amoutn").innerText = e.currentTarget.value;
        });


    }

}