<?php

namespace Flytedan\DanxLaravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'vapor:encrypt')]
class VaporEncryptCommand extends Command
{
	protected        $signature   = 'vapor:encrypt {env}';
	protected static $defaultName = 'vapor:encrypt';
	protected        $description = 'Encrypt an environment file for use w/ deployed laravel vapor environments';

	protected Filesystem $files;

	public function __construct(Filesystem $files)
	{
		parent::__construct();
		$this->files = $files;
	}

	public function handle()
	{
		$key = config('app.encryption_key');

		if (!$key) {
			$generatedKey = base64_encode(random_bytes(64));
			$this->components->error("Encryption key not found. \n\n\tPlease install this key in your .env file: LARAVEL_ENV_ENCRYPTION_KEY=base64:$generatedKey");

			return Command::FAILURE;
		}

		$cipher = 'AES-256-CBC';
		$key    = $this->parseKey($key);
		$env    = $this->argument('env');

		$environmentFile = base_path('.env') . '.' . $env;
		$encryptedFile   = $environmentFile . '.encrypted';

		if (!$this->files->exists($environmentFile)) {
			$this->components->error('Environment file not found.');

			return Command::FAILURE;
		}

		try {
			$encrypter = new Encrypter($key, $cipher);

			$this->files->put(
				$encryptedFile,
				$encrypter->encrypt($this->files->get($environmentFile))
			);
		} catch(Exception $e) {
			$this->components->error($e->getMessage());

			return Command::FAILURE;
		}

		$this->components->info('Environment successfully encrypted.');

		$this->components->twoColumnDetail('Key', 'base64:' . base64_encode($key));
		$this->components->twoColumnDetail('Cipher', $cipher);
		$this->components->twoColumnDetail('Encrypted file', $encryptedFile);

		$this->newLine();

		return Command::SUCCESS;
	}

	/**
	 * Parse the encryption key.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function parseKey(string $key)
	{
		if (Str::startsWith($key, $prefix = 'base64:')) {
			$key = base64_decode(Str::after($key, $prefix));
		}

		return $key;
	}
}
