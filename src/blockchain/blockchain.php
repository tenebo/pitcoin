<?php
$f1 = fopen('current_transactions.php', 'r');
$JSON_TRANSACTIONS = "";
while(!feof($f1)) {
  $JSON_TRANSACTIONS = $JSON_TRANSACTIONS . fgets($f1);
}
$current_transactions = json_decode($JSON_TRANSACTIONS, true); 
$f = fopen('index.php', 'r');
$JSON_CHAIN = "";
while(!feof($f)) {
  $JSON_CHAIN = $JSON_CHAIN . fgets($f);
}

function rsa_generate_keys( $bits = 512, $digest_algorithm = 'sha256')
{
    $res = openssl_pkey_new(array(
        'digest_alg' => $digest_algorithm,
        'private_key_bits' => $bits,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ));
    
    openssl_pkey_export($res, $private_key);
    
    $public_key = openssl_pkey_get_details($res);
    $public_key = $public_key['key'];
    
    return array(
        'private_key' => base64_encode($private_key),
        'public_key' => base64_encode($public_key),
    );
}



function rsa_encrypt($plaintext, $public_key)
{
    $public_key = base64_decode($public_key);
    $plaintext = gzcompress($plaintext);
    
    
    $pubkey_decoded = @openssl_pkey_get_public($public_key);
    if ($pubkey_decoded === false) return false;
    
    $ciphertext = false;
    $status = @openssl_public_encrypt($plaintext, $ciphertext, $pubkey_decoded);
    if (!$status || $ciphertext === false) return false;
    
    
    return base64_encode($ciphertext);
}


function rsa_decrypt($ciphertext, $private_key)
{
    $private_key = base64_decode($private_key);
    $ciphertext = @base64_decode($ciphertext, true);
    if ($ciphertext === false) return false;
    
    
    $privkey_decoded = @openssl_pkey_get_private($private_key);
    if ($privkey_decoded === false) return false;
    
    $plaintext = false;
    $status = @openssl_private_decrypt($ciphertext, $plaintext, $privkey_decoded);
    @openssl_pkey_free($privkey_decoded);
    if (!$status || $plaintext === false) return false;
    
    
    $plaintext = @gzuncompress($plaintext);
    if ($plaintext === false) return false;
    
    
    return $plaintext;
}

$chain = json_decode($JSON_CHAIN, true); 
function new_block($proof, $previous_hash){
    global $chain, $current_transactions;
    $block = array(
        'index' => sizeof($chain) + 1,
        'timestamp' => date("Ymdhis"),
        'transactions' => $current_transactions,
        'proof' => $proof,
        'previous_hash' => $previous_hash,
    );
    $current_transactions = [];

    array_push($chain, $block);
    return $block;
}

function new_transaction($sender, $recipient, $amount){
    global $current_transactions;
    array_push($current_transactions, array( 
        'sender' => $sender,
        'recipient' => $recipient,
        'amount' => $amount,
        'hash' => hash('sha256', $sender . $recipient . $amount),
      )
    );
    return $block[-1] + 1;
}


function proof_of_work($last_proof){
    
    $proof = 0;
    while (valid_proof($last_proof, $proof) === 0){
      $proof ++;
    }
    return $proof;
}

function valid_proof($last_proof, $proof){
    
    $guess = $last_proof . $proof;
    $guess_hash = hash('sha256',$guess);
    if(substr($guess_hash, 0, 5) === "00000"){
       return 1;
    }
    else{
       return 0;
    }
}
//15000000
if (sizeof($chain) > 0){
  $reward = "0.001";
}
if (sizeof($chain) > 1000){
  $reward = "0.0005";
}
if (sizeof($chain) > 10000){
  $reward = "0.00025";
}
if (sizeof($chain) > 100000){
  $reward = "0.000125";
}
function mine($miner){
  global $block, $chain, $current_transactions, $reward;
  $last_proof = $chain[sizeof($chain)-1]['proof'];
  $proof = proof_of_work($last_proof);
  
  new_transaction("null", $miner, $reward);
  $previous_hash = hash('sha256', implode("", $chain[sizeof($chain)-1]));
  $block = new_block($proof, $previous_hash);
  
  $response = array(
    'message' => "New Block Forged",
    'index' => $block['index'],
    'transactions' => $block['transactions'],
    'proof' => $block['proof'],
    'previous_hash' => $block['previous_hash'],
  );
  return json_encode($response);
}
function save(){
  global $current_transactions, $chain;
  $save_transaction = fopen('current_transactions.php', 'w');
  fwrite($save_transaction, json_encode($current_transactions));
  fclose($save_transaction);
  $save_chain = fopen('index.php', 'w');
  fwrite($save_chain, json_encode($chain));
  fclose($save_chain);
}
//echo json_encode
$func = $_GET['func'];
if ($func === 'new_transaction'){
  $sender = $_GET['sender'];
  $recipient = $_GET['recipient'];
  $amount = $_GET['amount'];
  $ciphertext = $_GET['ciphertext'];
  if($sender !== null && $recipient !== null && $amount !== null && substr(hash("sha256", $sender . $recipient . $amount),0,16) === rsa_decrypt($ciphertext, $sender)){
    echo "1";
    new_transaction($sender, $recipient, $amount);
    save();
  }
}else if ($func === 'mine'){
  $miner = $_GET['miner'];
  mine($miner);
  echo "1";
  save();
}
?>
