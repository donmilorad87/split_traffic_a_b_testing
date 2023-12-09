<div class="df fdc wrap">
    <h3><?php _e('A/B Split Traffic Testing', 'split_traffic_a_b_testing') ?></h3>
    <p> <?php _e('In settings you can see traffic, conversion and unique conversion counters for Control and Experiment page', 'split_traffic_a_b_testing') ?></p>
    <table>
        <thead>
            <tr>
                <th colspan="2"><?php _e('Traffic Counter', 'split_traffic_a_b_testing') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th><?php _e('Number of traffic landings on control page:', 'split_traffic_a_b_testing') ?></th>
                <th><?php _e('Number of traffic landings on experiment page:', 'split_traffic_a_b_testing') ?></th>
            </tr>
            <tr>
                <td><?php echo $args['control_traffic_counter']; ?></td>
                <td><?php echo $args['experiment_traffic_counter']; ?></td>
            </tr>
        </tbody>
    </table>
    <br>
    <table>
        <thead>
            <tr>
                <th colspan="2"><?php _e('Conversation counter', 'split_traffic_a_b_testing') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th><?php _e('Number of conversations on control page:', 'split_traffic_a_b_testing') ?></th>
                <th><?php _e('Number of conversations on experiment page:', 'split_traffic_a_b_testing') ?></th>
            </tr>
            <tr>
                <td><?php echo $args['control_conversation_counter']; ?></td>
                <td><?php echo $args['experiment_conversation_counter']; ?></td>
            </tr>
        </tbody>
    </table>
    <br>
    <table>
        <thead>
            <tr>
                <th colspan="2"><?php _e('Unique conversations', 'split_traffic_a_b_testing') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th><?php _e('Number of unique conversations on control page:', 'split_traffic_a_b_testing') ?></th>
                <th><?php _e('Number of unique conversations on experiment page:', 'split_traffic_a_b_testing') ?></th>
            </tr>
            <tr>
                <td><?php echo $args['control_unique_conversation_counter']; ?></td>
                <td><?php echo $args['experiment_unique_conversation_counter']; ?></td>
            </tr>
        </tbody>
    </table>
    <br>


    <div class="df aic g2 b1 p1rem">

        <div class="df fdc p1dot5rem b1 f1">
            <label for="amount_for_unique_expiry"><?php _e('Select amount of units for unique expiry:', 'split_traffic_a_b_testing') ?>
                <strong id="value_amoutn"><?php echo $args['amount_for_unique_expiry']; ?></strong>
            </label>
            <input type="range" id="amount_for_unique_expiry" name="amount_for_unique_expiry" min="1" max="60" step="1" value="<?php echo $args['amount_for_unique_expiry']; ?>">
        </div>
        <div class="df fdc p1dot5rem b1 f1">
            <label for="unit_for_unique_expiry"><?php _e('Select time value type for unique expiry:', 'split_traffic_a_b_testing') ?></label>
            <select id="unit_for_unique_expiry" name="unit_for_unique_expiry">

                <?php

                    foreach ($args['unit_for_unique_expiry_types'] as $value) {

                        echo '<option value="' . $value . '"' . ($value === $args['unit_for_unique_expiry'] ? 'selected' : '') . '>' .  __(ucfirst($value), 'split_traffic_a_b_testing') . '</option>';
                    }

                ?>


            </select>
        </div>
        <input type="button" value="<?php _e('Submit', 'split_traffic_a_b_testing') ?>" class="f1 button button-primary button-large" id="submit_new_values_for_expiry">



    </div>

    <dialog id="loadingDialog">

        <div class="ripple">
            <div></div>
            <div></div>
        </div>

    </dialog>

</div>