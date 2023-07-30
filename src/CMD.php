<?php

namespace Kyziss\CmdRunner;

/**
 * Run CMD
 * 
 * @author khuongtq
 */
class CMD
{
	/**
	 * @var string command
	 */
	private $command;
	private $directory;
	private $env;
	private $options;

	private $exitCode;
	private $output;
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
	 * @param string $command
	 */
	public function run($command)
	{
		$this->command = $command;
		$descriptorspec = [
			0 => ['pipe', 'r'], // stdin
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'], // stderr
		];
		$pipes = [];
		$process = proc_open($command, $descriptorspec, $pipes, $this->directory, $this->env, $this->options);
		if (!$process)
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

		return $this;
	}

	/**
	 * @return bool
	 */
	public function ok()
	{
		return $this->exitCode === 0;
	}

	/**
	 * @return string
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * @return int
	 */
	public function getExitCode()
	{
		return $this->exitCode;
	}

	/**
	 * @return string[]
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * @return string
	 */
	public function getOutputAsString()
	{
		return implode("\n", $this->output);
	}

	/**
	 * @return string|NULL
	 */
	public function getOutputLastLine()
	{
		$lastLine = end($this->output);
		return is_string($lastLine) ? $lastLine : NULL;
	}

	/**
	 * @return bool
	 */
	public function hasOutput()
	{
		return !empty($this->output);
	}

	/**
	 * @return string[]
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @return bool
	 */
	public function hasError()
	{
		return !empty($this->error);
	}

	/**
	 * @return string
	 */
	public function toText()
	{
		return '$ ' . $this->getCommand() . "\n\n"
			. "---- STDOUT: \n\n"
			. implode("\n", $this->getOutput()) . "\n\n"
			. "---- STDERR: \n\n"
			. implode("\n", $this->getError()) . "\n\n"
			. '=> ' . $this->getExitCode() . "\n\n";
	}
}
