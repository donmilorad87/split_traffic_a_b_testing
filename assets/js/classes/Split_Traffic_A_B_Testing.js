export default class Split_Traffic_A_B_Testing {

    constructor() {
        this.setConversationEvent();
    }


    /**
     * setConversationEvent function is used for sending conversation click to server
    */

    setConversationEvent() {
        document.querySelector('#conversation_link')?.addEventListener('click', (e) => {

            e.preventDefault()

            let conversationForm = document.querySelector('#conversation_form')
            
            let conversation_pointer =  conversationForm.elements['conversation_pointer'].value
            let conversation_subbmision_nonce = conversationForm['conversation_subbmision_nonce'].value
            let data = new URLSearchParams({
                'action': 'conversation_counter_fetch',
                conversation_pointer,
                conversation_subbmision_nonce
                
            })
            
    
            let url = conversationForm.action

            let loadingDialog = document.querySelector('#loadingDialog')

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
     * setCookie function is used for getting cookie to browser
    */

    getCookie = (cookieName) => {
        var cookies = document.cookie.split(';');

        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].trim();
            // Check if the cookie starts with the desired name
            if (cookie.indexOf(cookieName + '=') === 0) {
                // Return the value of the cookie
                return decodeURIComponent(cookie.substring(cookieName.length + 1));
            }
        }

        // Cookie not found
        return '';
    }

}