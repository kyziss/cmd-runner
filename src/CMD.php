<?php

namespace Kyziss\CmdRunner;

/**
 * Execute CMD
 * 
 * @author khuongtq
 */
class CMD
{
	/**
	 * @var string Command
	 */
	private $command;

	/**
	 * @var string Directory to execute the command
	 */
	private $directory;

	/**
	 * @var array|null
	 */
	private $env;

	/**
	 * @var array|null
	 */
	private $options;

	/**
	 * @var int|null If it is 0, it is successful
	 */
	private $exitCode;

	/**
	 * @var string|array|null
	 */
	private $output;

	/**
	 * @var string|array|null
	 */
	private $error;

	public function __construct($directory, $env = NULL, $options = NULL)
	{
		$this->directory = $directory;
		$this->env = $env;
		$this->options = $options;
		if (!$options) {
			$this->options = [
				'bypass_shell' => TRUE,
			];
		}
	}

	/**
	 * Execute the command
	 * 
	 * @param string $command
	 * @return \Kyziss\CmdRunner\CMD
	 */
	public function execute($command)
	{
		try {
			$this->command = $command;
			$descriptorspec = [
				0 => ['pipe', 'r'], // stdin
				1 => ['pipe', 'w'], // stdout
				2 => ['pipe', 'w'], // stderr
			];
			$pipes = [];
			$process = proc_open($command, $descriptorspec, $pipes, $this->directory, $this->env, $this->options);
			if (!is_resource($process))
				throw new \Exception("Executing of command '$command' failed (directory $this->directory).");

			// Reset output and error
			stream_set_blocking($pipes[1], FALSE);
			stream_set_blocking($pipes[2], FALSE);
			$out = '';
			$err = '';

			while (TRUE) {
				// Read standard output
				$stdoutOutput = stream_get_contents($pipes[1]);
				if (is_string($stdoutOutput)) $out .= $stdoutOutput;
				// Read error output
				$stderrOutput = stream_get_contents($pipes[2]);
				if (is_string($stderrOutput)) $err .= $stderrOutput;
				// We are done
				if (
					(feof($pipes[1]) || $stdoutOutput === FALSE) &&
					(feof($pipes[2]) || $stderrOutput === FALSE)
				) {
					break;
				}
			}

			$exitCode = proc_close($process);
			$this->exitCode = $exitCode;
			$this->output = $out;
			$this->error = $err;
		} catch (\Exception $e) {
			$this->error = $e->getMessage();
			$this->exitCode = 999;
			if (preg_match('/error code: (\d+)/', $this->error, $matches))
				$this->exitCode = (int) $matches[1];
		}

		return $this;
	}

	/**
	 * Check if success
	 * 
	 * @return bool
	 */
	public function ok()
	{
		return $this->exitCode === 0;
	}

	/**
	 * Get command
	 * 
	 * @return string
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * Get directory
	 * 
	 * @return string
	 */
	public function getDirectory()
	{
		return $this->directory;
	}

	/**
	 * Get exitCode
	 * 
	 * @return int
	 */
	public function getExitCode()
	{
		return $this->exitCode;
	}

	/**
	 * Get output
	 * 
	 * @return string|array|null
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * Check output
	 * 
	 * @return bool
	 */
	public function hasOutput()
	{
		return !is_null($this->getOutput()) && $this->getOutput() !== '';
	}

	/**
	 * Get output as string
	 * 
	 * @return string
	 */
	public function getOutputAsString()
	{
		return is_array($this->getOutput()) ? implode("\n", $this->getOutput()) : $this->getOutput();
	}

	/**
	 * Get last line of output
	 * 
	 * @return string|null
	 */
	public function getOutputLastLine()
	{
		$output = trim($this->getOutput());
		if (!is_array($output))
			$output = explode("\r\n", $output);
		if (reset($output) === '') array_shift($output);
		if (end($output) === '') array_pop($output);
		$lastLine = end($output);

		return is_string($lastLine) ? $lastLine : NULL;
	}

	/**
	 * Get error
	 * 
	 * @return string|array|null
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Check error
	 * 
	 * @return bool
	 */
	public function hasError()
	{
		return !is_null($this->getError()) && $this->getError() !== '';
	}

	/**
	 * Get command, output, error and exit code as string
	 * 
	 * @return string
	 */
	public function toText()
	{
		return '$ ' .
			implode("\n\n", [
				$this->getCommand(),
				"----- OUTPUT: ",
				(is_array($this->getOutput()) ? implode("\n", $this->getOutput()) : $this->getOutput()),
				"----- ERROR: ",
				(is_array($this->getError()) ? implode("\n", $this->getError()) : $this->getError()),
				"---- EXIT CODE: " . $this->getExitCode(),
			]);
	}
}
