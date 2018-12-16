<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require ("class/resenderforgotproducts/resenderforgotproducts.php");
//рассылка писем

ReSenderForgotProducts::execute();
echo 'ok, all mail message send';