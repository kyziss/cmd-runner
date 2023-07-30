# CMD Runner

[![Latest Stable Version](https://img.shields.io/badge/stable-v1.0.0-yellow.svg)](https://github.com/kyziss/cmd-runner/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/kyziss/cmd-runner/blob/main/LICENSE)

The library works with PHP, Laravel.
## Installation

Add repository to composer.json:

```json
	"repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:kyziss/cmd-runner.git"
        }
    ]
```

Install using composer:

```
composer requỉed kyziss/cmd-runner
```

## Usage

```php
use Kyziss\CmdRunner\CMD;

$path = "\path\to\directory"; // your directory
$command = "ipconfig"; // your command
$cmd = new CMD($path);
$exec = $cmd->execute($command);

if (!$exec->ok()) {
	return $exec->getError();
}

return $exec->getOutput();
```

---

License: [MIT License](LICENSE)

Author: Trần Quang Khương