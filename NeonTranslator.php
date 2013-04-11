<?php

/**
 * Neon Translator
 * Work with translation written in .neon files. Has suppor for singular/plural and for replacing %chars with variables.
 * More info about neon at http://ne-on.org/
 *
 * @author Lukáš "GreenMan" Kurčík <lukas.kurcik@gmail.com>
 * @version 1.0
 * @copyright (c) 2013, Lukáš "GreenMan" Kurčík
 */
class NeonTranslator implements Nette\Localization\ITranslator {

    /**
     * Array filled with translation
     * @var array
     */
    private $translation;

    /**
     * FileStorage for cache
     * @var Nette\Caching\Storages\FileStorage
     */
    private $fileStroage;

    public function __construct($langagueFile, Nette\Caching\Storages\FileStorage $fileStorage = NULL) {
        $this->loadTranslation($langagueFile);
        $this->fileStroage = $fileStorage;
    }

    /**
     * Load translation from file or cache
     * @param string $langagueFile File with translation, %appDir% will be replace with constant APP_DIR
     */
    private function loadTranslation($langagueFile) {
        if ($this->fileStroage != NULL) {
            $cache = new Nette\Caching\Cache($this->fileStroage, 'NeonTranslator');
            $this->translation = $cache->load("_" . $langagueFile);
            $cacheSave = TRUE;
        }
        if ($this->translation == NULL) {
            $fileName = str_replace("%appDir%", APP_DIR, $langagueFile);
            if (!file_exists($fileName))
                throw new Nette\FileNotFoundException("Translation file '" . $fileName . "' wasn't found.");
            $neon = file_get_contents($fileName);
            $this->translation = Nette\Utils\Neon::decode($neon);
            if (isset($cacheSave))
                $cache->save("_" . $langagueFile, $this->translation, array(Cache::FILES => $fileName));
        }
    }

    /**
     * Translates the given string. If can't finde string, returns original message.
     * @param  string $message  message
     * @param  int $count plural count
     * @param mixed Variables for replacing %s in translation
     * @return string
     */
    public function translate($message, $count = NULL) {
        $original = $message;
        if (isset($this->translation[$message]))
            $message = $this->translation[$message];
        if (is_array($message))
            if (isset($message[$count]))
                $message = $message[$count];
            else {
                $counts = array_keys($message);
                $close = NULL;
                foreach ($counts as $key) {
                    if ($key < $count)
                        $close = $key;
                    if ($count < $key)
                        break;
                }
                if ($close != NULL)
                    $message = $message[$close];
                else
                    $message = $original;
            }
        if (func_num_args() > 2) {
            $replace = func_get_args();
            for ($i = 2; $i < count($replace); $i++)
                $message = preg_replace_callback('~([^/,]{0,1})(%.)~', function ($matches) use($replace, $i) {return $matches[1] . $replace[$i];}, $message, 1);
        }
        return $message;
    }

    /**
     * Shortcut for function translate
     * @param  string $message  message
     * @param  int $count plural count
     * @param mixed Variables for replacing %s in translation
     * @return string
     */
    public function _($message, $count = NULL) {
        return call_user_func_array(array($this, 'translate'), func_get_args());
    }

}
