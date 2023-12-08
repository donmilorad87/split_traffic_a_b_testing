<div class="df fdc wrap">

    $args = array(
    'amount_for_unique_expiry' => $amount_for_unique_expiry,
    'unit_for_unique_expiry' => $unit_for_unique_expiry,
    'unit_for_unique_expiry_types' => [
    'seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'
    ],
    'control_traffic_counter' => $control_traffic_counter,
    'experiment_traffic_counter' => $experiment_traffic_counter,
    'control_conversation_counter' => $control_conversation_counter,
    'experiment_conversation_counter' => $experiment_conversation_counter,
    'control_unique_conversation_counter' => $control_unique_conversation_counter,
    'experiment_unique_conversation_counter' => $experiment_unique_conversation_counter
    );

    <table>
        <thead>
            <tr>
                <th colspan="2">Trafic Counter</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>control_traffic_counter</td>
                <td>experiment_traffic_counter</td>
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
                <th colspan="2">Conversation counter</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>control_conversation_counter</td>
                <td>experiment_conversation_counter</td>
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
                <th colspan="2">The table header</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>control_unique_conversation_counter</td>
                <td>experiment_unique_conversation_counter</td>
            </tr>
            <tr>
                <td><?php echo $args['control_unique_conversation_counter']; ?></td>
                <td><?php echo $args['experiment_unique_conversation_counter']; ?></td>
            </tr>
        </tbody>
    </table>
    <br>
   

    <div>
        <form action="">
            <label for="amount_for_unique_expiry">Select a value: <span id="value_amoutn"></span></label>
            <input type="range" id="amount_for_unique_expiry" name="amount_for_unique_expiry" min="1" max="60" step="1" value="<?php echo $args['amount_for_unique_expiry']; ?>">

            <select id="unit_for_unique_expiry" name="unit_for_unique_expiry">

            <?php 
            
                foreach ($args['unit_for_unique_expiry_types'] as $value) {
                    if($value === $args['unit_for_unique_expiry']){
                        echo '<option value="'. $value .'" selected>'. ucfirst($value) .'</option>';
                    }else {
                        echo '<option value="'. $value .'">'. ucfirst($value) .'</option>';
                    }
                   
                    
                }

            ?>
       

            </select>
            <input type="button" value="Submit" id="submit_new_values_for_expiry">
        </form>


    </div>

    <dialog id="loadingDialog">

        <div class="ripple">
            <div></div>
            <div></div>
        </div>

    </dialog>

</div>