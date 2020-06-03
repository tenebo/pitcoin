import requests as req
import json
import hashlib
try:
    f = open("data", "r")
except:
    print("error:reinstall program(1)")
else:
    first = f.read()
    if first == "t":
        try:
            pri = open("private_key", "w")
            pub = open("public_key", "w")
        except:
            print("error: reinstall program(3)")
        else:
            keys = req.get("http://picoin.sites.ga/blockchain/newkeys.php").text
            keys = json.loads(keys)
            address = keys["private_key"]
            pri.write(address)
            secret = keys["public_key"]
            pub.write(secret)
            pri.close()
            pub.close()
            f1 = open("data", "w")
            f1.write("f")
            f1.close()
    elif first == "f":
        try:
            pri = open("private_key", "r")
            pub = open("public_key", "r")
        except:
            print("error: reinstall program(4)")
        else:
            address = pri.read()
            secret = pub.read()
            pri.close()
            pub.close()
    else:
        print("error: reinstall program(2)")
        f.close()
    amount = 0
    def reload():
        global address, amount
        amount=0
        blockchain = json.loads(req.get("http://picoin.sites.ga//blockchain/").text)
        for i in blockchain:
            for j in i["transactions"]:
                if str(j["recipient"]) == address:
                    amount += float(j["amount"])
                if str(j['sender']) == address:
                    amount -= float(j["amount"])
    reload()
    print("""
PI COIN wallet

wallet - see address
send - Pi coin
mine - mine Pi coin
reload - reload wallet
exit - exit wallet

Ask & contact to info@sites.ga
    """)
    while True:
        q = input(f"(you have {amount} PIC)>>> ")
        if q == "wallet":
            try:
                print(address)
            except:
                print("error: contact info@sites.ga")
        elif q == "send":
            recipient = input('To: ')
            try:
                send = float(input('Amount: '))
            except:
                print("type right amount")
            else:
                reload()
                if send <= amount and send > 0:
                    byte_str = (address + recipient + str(amount)).encode()
                    hash_v = hashlib.sha256(string=byte_str).hexdigest()[0:16]
                    encrypttext = req.get(f"http://picoin.sites.ga/blockchain/encrypt.php?plaintext={hash_v}&public_key={secret}").text
                    encrypttext = encrypttext.replace("+","%2B")
                    ans = req.get(f"http://picoin.sites.ga/blockchain/blockchain.php?func=new_transaction&sender={address}&recipient={recipient}&amount={send}&ciphertext={encrypttext}").text
                    reload()
                    if ans == "1":
                        print("transaction was confirm")
                    else:
                        print("transaction was not confirm, contact info@sites.ga")
                else:
                    print("not enough funds")
        elif q == "mine":
            proof=0
            ress = "0"
            while ress == "0":
                ress = req.get(f"http://picoin.sites.ga/blockchain/blockchain.php?func=mine&miner={address}&proof={proof}").text
                proof+=1
            reload()
        elif q=="reload":
            try:
                reload()
            except:
                print("error: conatact info@sites.ga")
        elif q=="exit":
            break
out = input("click any button to exit")
