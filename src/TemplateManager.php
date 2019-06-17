<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/symfony/yaml/Yaml.php';

class TemplateManager
{
    protected $quote;
    protected $application;
    protected $user;

    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $this->quote = (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;
        $this->application = ApplicationContext::getInstance();
        $this->user  = (isset($data['user'])  and ($data['user']  instanceof User))  ? $data['user']  : $this->application->getCurrentUser();
        $replaced->subject = $this->computeText($replaced->subject);
        $replaced->content = $this->computeText($replaced->content);
        return $replaced;
    }

    /**
     * @param $text
     * @return bool|mixed
     */
    private function computeText($text)
    {
        if ($this->quote) {
            $text = $this->SearchAndReplace($text);
        }
        return $text;
    }

    /**
     * @param String $text
     * @return bool|mixed
     */
    private function SearchAndReplace(String $text) {
        $_quoteFromRepository = QuoteRepository::getInstance()->getById($this->quote->id);
        $usefulObject = SiteRepository::getInstance()->getById($this->quote->siteId);
        $values = \Symfony\Component\Yaml\Yaml::parse( file_get_contents(__DIR__ . '/../config/keys.yaml'));
        $text = $this->searchAndreplaceInSession($text);
        foreach ($values as $k => $val) {
            if (strpos($text, '[quote:'.$k.']') !== false) {
                if ($val['value'] !== false) {
                    if ($k == 'first_name'):
                        $v = ($this->user) ? ucfirst(mb_strtolower($this->user->firstname)): '';
                    else:
                        $repo = ucfirst($val['className'])."Repository";
                        $repoObject = $repo::getInstance()->getById($this->quote->$val['className'].'Id');
                        if (($k == 'destination_link')):
                            $v = (isset($repoObject)) ? $usefulObject->url . '/' . $repoObject->countryName . '/quote/' . $_quoteFromRepository->id: '';
                        else:
                            $v = ($repoObject) ? $repoObject->{$val['value']}: '';
                        endif;
                    endif;

                } else {
                    $v = Quote::renderText($_quoteFromRepository);
                }

                $_SESSION['[quote:'.$k.']'] = $v;

                $text = str_replace(
                    '[quote:'.$k.']',
                     $v,
                    $text
                );
            }
        }
        return $text;
    }

    /**
     * @param String $text
     * @return mixed|String
     */
    private function searchAndreplaceInSession(String $text) {
        if($_SESSION !== null) {
            foreach ($_SESSION as $i => $item) {
                return str_replace(
                    $i,
                    $item,
                    $text
                );
            }
        }
        return $text;
    }
}
