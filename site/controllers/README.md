# IMC API controller class #

Make sure you have mcrypt module enabled (e.g. $ sudo php5enmod mcrypt)

Every request should contain token, m_id, l

* where *token* is the m-crypted "json_encode(array)" of username, password, timestamp in the following form:

**{'u':'username','p':'plain_password','t':'1439592509'}**

all casted to strings including the UNIX timestamp time()

* where *m_id* is the modality ID according to the REST/API key definition in the administrator side
* where *l* is the 2-letter language code used for for the responses translation (en, el, de, es, etc)

Every token is allowed to be used **only once** to avoid MITM attacks

Check **helpers/MCrypt.php** for details on how to use Rijndael-128 AES encryption algorithm

Please note that for better security it is highly recommended to protect your site with SSL (https)

