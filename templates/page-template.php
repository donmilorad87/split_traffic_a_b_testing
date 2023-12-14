<div class="df aic jcc">
    <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" id="conversation_form">

        <input type="hidden" id="conversation_pointer" name="conversation_pointer" value="<?php echo $args['conversation_pointer'] ?>">
        <input type="hidden" name="conversation_subbmision_nonce" id="conversation_subbmision_nonce" value="<?php echo $args['conversation_subbmision_nonce'] ?>">

        <input type="submit" class="conversationLink" value="<?php _e('Conversation link', 'split_traffic_a_b_testing') ?>" id="conversation_link">

    </form>


    <dialog id="loadingDialog">

        <div class="spinner">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>

    </dialog>

</div>