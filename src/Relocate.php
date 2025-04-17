<?php

namespace Tualo\Office\PUG;

class Relocate
{
    public function to(string $url): void
    {
        header("Location: " . $url);
        exit();
    }
}
