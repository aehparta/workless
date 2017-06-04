<?php

class BinController extends Core\Controller
{
	public function getAction($key)
	{
		$this->kernel->historyDisable();
		$key = urldecode($key);
		$bin = new Bin();

		if (!$bin->read($key, $content))
		{
			$content = $this->input('default');
			if (!$content)
			{
				throw new RedirectException($this->kernel->config['urls']['error']);
			}
		}

		return $this->renderRaw($content);
	}

	public function getSummernoteAction($key)
	{
		$this->kernel->historyDisable();
		$key               = urldecode($key);
		$params            = array();
		$params['key']     = $key;
		$params['default'] = $this->input('default');
		return $this->display('block-summernote.html', $params);
	}

	public function dlAction($key)
	{
		$this->kernel->historyDisable();
		$key = urldecode($key);
		$bin = new Bin();
		$format = $this->inputDefault('zip', 'format');

		$path = $bin->getFilepath($key);
		if (!file_exists($path))
		{
			throw new Exception404($this->tr('msg/error/file-not-exist', $key));
		}

		if (is_file($path))
		{
			if (!$bin->readfile($key, true, true))
			{
				throw new Exception404($this->tr('msg/error/file-not-exist', $key));
			}
		}
		else if (is_dir($path))
		{
			if (!$bin->export($key, $format, true, true))
			{
				throw new Exception404($this->tr('msg/error/file-not-exist', $key));
			}
		}
		else
		{
			/* unsupported type of file */
			throw new Exception404($this->tr('msg/error/file-not-exist', $key));
		}

		return true;
	}

	public function imageAction($key)
	{
		$this->kernel->historyDisable();
		$key = urldecode($key);
		$bin = new Bin();

		$filepath = $bin->getFilepath($key);
		if (!is_file($filepath))
		{
			throw new Exception404($this->tr('msg/error/file-not-exist', $key));
		}

		$mimetype = mime_content_type($filepath);
		if (strpos($mimetype, 'image/') !== 0)
		{
			throw new Exception404($this->tr('msg/error/file-not-exist', $key));
		}

		$w            = $this->input('w');
		$h            = $this->input('h');
		$crop_w       = $this->input('crop_w');
		$crop_h       = $this->input('crop_h');
		$crop_x       = $this->input('crop_x');
		$crop_y       = $this->input('crop_y');
		$fit_w        = $this->input('fit_w');
		$fit_h        = $this->input('fit_h');
		$fit_position = $this->input('fit_position');
		if ($fit_position === null)
		{
			$fit_position = 'center';
		}
		$modified = filemtime($filepath);

		$cache_key = $key;
		$cache_key .= '/' . intval($w) . '*' . intval($h);
		$cache_key .= '_' . intval($crop_w) . '*' . intval($crop_h);
		$cache_key .= '/' . intval($crop_x) . '*' . intval($crop_y);
		$cache_key .= '_' . intval($fit_w) . '*' . intval($fit_h);
		$cache_key .= '/' . $fit_position;
		$cache_file = $this->kernel->getCacheFile($cache_key, 'bin');

		if (is_file($cache_file))
		{
			if ($modified == filemtime($cache_file))
			{
				if (!$bin->setHeaders($cache_file, true))
				{
					readfile($cache_file);
				}
				return true;
			}
			unlink($cache_file);
		}

		$manager = new Intervention\Image\ImageManager(array('driver' => 'imagick'));
		$image   = $manager->make($filepath);
		if (!$image)
		{
			throw new Exception404($this->tr('msg/error/file-not-exist', $key));
		}

		/* fit if either fit width or height given */
		if ($fit_w !== null || $fit_h !== null)
		{
			$image->fit($fit_w, $fit_h, null, $fit_position);
		}

		/* resize if either width or height given */
		if ($w !== null || $h !== null)
		{
			$image->resize($w, $h);
		}

		/* crop if either crop width or height given */
		if ($crop_w !== null || $crop_h !== null)
		{
			$image->crop($crop_w, $crop_h, $crop_x, $crop_y);
		}

		/* save file and set modification time to same as original */
		$image->save($cache_file);
		touch($cache_file, $modified);

		/* echo file contents */
		header('Content-Type: ' . $mimetype);
		readfile($cache_file);

		return true;
	}

	public function uploadAction($path)
	{
		$this->kernel->historyDisable();
		$path = urldecode($path);

		$bin = new Bin();
		if (!$bin->upload($path, $files))
		{
			http_response_code(500);
			$this->kernel->msg('error', $this->tr('msg/error/upload'));
		}
		else
		{
			foreach ($files as $file)
			{
				if ($file['error'])
				{
					$this->kernel->msg('error', $this->tr('msg/success/upload-file', $file['name']));
				}
				else
				{
					// $this->kernel->msg('success', $this->tr('msg/success/upload-file', $file['name']));
				}
			}
		}

		return true;
	}

	public function jsonAction($key)
	{
		$this->kernel->historyDisable();
		$key    = urldecode($key);
		$bin    = new Bin();
		$params = array();

		if ($this->kernel->method == 'put')
		{
			$data = @json_decode($this->put, true);
			if ($data === null)
			{
				throw new Exception500($this->tr('msg/error/invalid-json'));
			}
			if (!$bin->save($key, json_encode($data)))
			{
				throw new Exception500($this->tr('msg/error/save'));
			}
		}
		else if ($this->kernel->method == 'post')
		{
			$data = $this->input('data');
			if (!$data)
			{
				$this->kernel->msg('error', $this->tr('msg/error/invalid-json-post'));
			}
			else if (!$bin->save($key, json_encode($data)))
			{
				$this->kernel->msg('error', $this->tr('msg/error/save'));
			}
			throw new RedirectException($this->kernel->historyPop());
		}

		return $this->display(null, $params);
	}

	public function folderAction($key)
	{
		$this->kernel->historyDisable();
		$key    = urldecode($key);
		$bin    = new Bin();
		$params = array();

		if ($this->kernel->method == 'get')
		{
			$files = $bin->getFiles($key);
			if (!$files)
			{
				throw new Exception404('Invalid path: ' . $key);
			}
			$params = $files;
		}
		else if ($this->kernel->method == 'post' || $this->kernel->method == 'put')
		{
			if (!$bin->folderCreate($key))
			{
				throw new Exception500('Unable to create new folder: ' . $key);
			}
		}

		$this->display(null, $params);
	}

	public function fileAction($key)
	{
		$this->kernel->historyDisable();
		$key    = urldecode($key);
		$bin    = new Bin();
		$params = array();

		if ($this->kernel->method == 'get')
		{
			$stat = $bin->stat($key);
			if (!$stat)
			{
				throw new Exception404($this->tr('msg/error/file-not-exist', $key));
			}
			$params = $stat;
		}
		else if ($this->kernel->method == 'put')
		{
			if (!$bin->save($key, $this->put))
			{
				throw new Exception500($this->tr('msg/error/save'));
			}
			$stat = $bin->stat($key);
			if (!$stat)
			{
				throw new Exception404($this->tr('msg/error/file-not-exist', $key));
			}
			$params = $stat;
		}
		else if ($this->kernel->method == 'delete')
		{
			$stat = $bin->stat($key);
			if (!$stat)
			{
				throw new Exception404($this->tr('msg/error/file-not-exist', $key));
			}
			if (!$bin->delete($key))
			{
				throw new Exception404($this->tr('msg/error/file-delete', $key));
			}
			$params = $stat;
		}

		$this->display(null, $params);
	}
}
