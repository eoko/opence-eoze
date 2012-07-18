<?php

namespace eoko\hg;

use InvalidArgumentException;
use RuntimeException;

/**
 * Adapter to retrieve information about a mercurial repository.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Ã‰ric Ortega <eric@planysphere.fr>
 * @since 28 mars 2012
 *
 * @version 0.1.0 12/04/12 18:31
 * @version 1.0.0 12/04/12 22:17
 */
class Mercurial {

	private $path;

	private $hg = '/usr/bin/hg';
	private $revisionNodes;
	private $nodeRevisions;

	/**
	 * Creates a new Mercurial object.
	 * @param string|null $repository
	 */
	public function __construct($repository = null) {
		if ($repository !== null) {
			$this->setRepository($repository);
		}
	}

	private function run($cmd) {
		return $this->runInPath($cmd, $this->path);
	}

	private function runInPath($cmd, $path) {

		$command = "$this->hg $cmd";

		$process = proc_open($command, array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		), $pipes, $path);

		if (is_resource($process)) {

			$out = stream_get_contents($pipes[1]);
			$err = stream_get_contents($pipes[2]);

			$exitCode = proc_close($process);

			if ($exitCode !== 0) {
				throw new RuntimeException("Mercurial error: $err", $exitCode);
			}

			return trim($out);
		}

		else {
			throw new RuntimeException("Invalid command: " . $command);
		}
	}

	private function clearCachedVars() {
		$this->nodeRevisions
				= $this->revisionNodes
				= null;
	}

	/**
	 * Sets the mercurial repository to be used.
	 * @param string $path
	 * @return Mercurial
	 */
	public function setRepository($path) {

		if ($path !== $this->path) {

			if ($path !== null) {
				// Test that dir exists
				if (!is_dir($path)) {
					throw new InvalidArgumentException('Repository path is not a directory: ' . $path);
				}
				// Test that there is a mercurial repository in there
				system("cd $path && $this->hg id 1>/dev/null 2>/dev/null", $return);
				if ($return === 255) {
					throw new InvalidArgumentException("No mercurial repository found in: $path");
				}
			}

			// Clear cached variables
			$this->clearCachedVars();
		}

		$this->path = $path;

		return $this;
	}

	/**
	 * Alias for {@link #getCurrentNode} method.
	 * @return string
	 */
	public function getId() {
		return $this->getCurrentNode();
	}

	/**
	 * Gets the changeset identification hash, as a 13-character hexadecimal string.
	 * @return string
	 */
	public function getCurrentNode() {
		return $this->run('id -i');
	}

	/**
	 * Gets the list of all repository-local changeset revision number.
	 *
	 * Returns an array of the form:
	 *
	 *     array(
	 *       $rev => $node,
	 *       ...
	 *     )
	 *
	 * @return string[]
	 */
	public function getRevisions() {
		$this->parseRevisions();
		return $this->revisionNodes;
	}

	/**
	 * Gets the list of all changesets as an array of the form:
	 *
	 *     array(
	 *       $node => $rev,
	 *       ...
	 *     )
	 *
	 * @return string[]
	 */
	public function getNodes() {
		$this->parseRevisions();
		return $this->nodeRevisions;
	}

	/**
	 * Gets the repository-local revision number matching the given changeset identification hash.
	 * @param string $node
	 * @return int
	 */
	public function getNodeRevision($node) {
		$this->parseRevisions();
		$node = substr($node, 0, 12);
		return isset($this->nodeRevisions[$node]) ? $this->nodeRevisions[$node] : null;
	}

	/**
	 * Gets the changeset identification hash matching the given repository-local revision number.
	 * @param int $rev
	 * @param bool $require
	 * @return string
	 */
	public function getRevisionNode($rev, $require = false) {
		$this->parseRevisions();
		return isset($this->revisionNodes[$rev]) ? $this->revisionNodes[$rev] : null;
	}

	private function parseRevisions() {
		if ($this->revisionNodes === null) {

			$lines = $this->run("log --template '{rev}:{node}\n'");
			$this->revisionNodes = array();
			foreach (explode(PHP_EOL, $lines) as $line) {
				list($rev, $node) = explode(':', $line);
				$node = substr($node, 0, 12);
				$this->revisionNodes[(int) $rev] = $node;
			}

			ksort($this->revisionNodes);

			$this->nodeRevisions = array();
			foreach ($this->revisionNodes as $rev => $node) {
				$this->nodeRevisions[$node] = $rev;
			}
		}
	}

	/**
	 * Gets the node hash identifier for the given revision, that can be specified
	 * either as a local repository revision number or a changeset hash identifier.
	 *
	 * @param string $rev Code revision.
	 *
	 * @return string
	 */
	private function getHash($rev) {
		if (preg_match('/^\d+$/', $rev)) {
			return $this->getRevisionNode($rev);
		} else {
			return $rev;
		}
	}

	/**
	 * Gets a range of revision between two revisions.
	 *
	 * The two revision bound will be included in the returned array.
	 *
	 * The parameters can be either repository-local revision number or changeset
	 * hash identifier.
	 *
	 * @param int|string $from Starting revision.
	 * @param int|string $to   Target revision.
	 *
	 * @return string[] An array containging the node identifier hash of all the changesets
	 * in the range.
	 */
	public function getNodeRange($from, $to) {

		$from = $this->getNodeRevision($this->getHash($from));
		$to   = $this->getNodeRevision($this->getHash($to));

		$changesets = array();
		for ($i = $from; $i <= $to; $i++) {
			$changesets[] = $this->getRevisionNode($i, true);
		}

		return $changesets;
	}

	/**
	 * Gets the {@link Mercurial} adapter for a sub repository.
	 *
	 * @param string $relativePath
	 *
	 * @return Mercurial
	 */
	public function getSubRepository($relativePath) {
		return new Mercurial($this->path . DIRECTORY_SEPARATOR . $relativePath);
	}

	/**
	 * Gets a range of a subrepository revision between two revisions.
	 *
	 * @param string $subRepository Name of the subrepository.
	 * @param int|string $from  Starting rev of the parent repository.
	 * @param int|string $to    Target rev of the parent repository.
	 *
	 * @return string[]
	 */
	public function getSubRepoNodeRange($subRepository, $from, $to) {

		$sub = $this->getSubRepository($subRepository);

		$rgName = preg_quote($subRepository, '/');

		$cats = array(
			'from' => $this->run("cat -r $from .hgsubstate"),
			'to'   => $this->run("cat -r $to .hgsubstate"),
		);

		foreach ($cats as $rev => $cat) {
			if (preg_match("/^(?P<rev>\w+) $rgName$/m", $cat, $matches)) {
				$subs[$rev] = subStr($matches['rev'], 0, 12);
				if (preg_match('/^0+$/', $subs[$rev])) {
					$subs[$rev] = $sub->getHash(0);
				}
			} else {
				$subs[$rev] = null;
			}
		}

		// If $subTo has been found, that means that we cross the sub repo, at least
		// partially.
		if ($subs['to'] !== null) {
			// If from is NULL, but not to, that means that the searched range starts
			// from revision 0 of the sub repo.
			if ($subs['from'] === null) {
				$subs['from'] = $sub->getHash(0);
			}
			// Use subrepo to return range
			return $sub->getNodeRange($subs['from'], $subs['to']);
		}

		// The whole range takes place before the subrepo was created
		else {
			return array();
		}
	}

	/**
	 * Updates the working directory to the specified revision.
	 *
	 * The revision can be specified either with a repository-local revision
	 * number, or a changeset idendifier hash string.
	 *
	 * @param int|string $rev
	 */
	public function update($rev) {
		$this->run("update $rev");
	}

	/**
	 * Returns `true` if the repository contains modified or untracked files.
	 * @return bool
	 */
	public function isModified() {
		return $this->run('st') !== '';
	}

	/**
	 * Gets the path to the mercurial repository.
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
}