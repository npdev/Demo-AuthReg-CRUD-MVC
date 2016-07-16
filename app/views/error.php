<?php

if (!empty($this->errors)) {
    foreach ($this->errors as $error) {
        echo "<p class=\"alert alert-error\">" . $error . "</p>" . "\n";
    }
}
