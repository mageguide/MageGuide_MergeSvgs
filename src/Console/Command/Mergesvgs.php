<?php


namespace MageGuide\MergeSvgs\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Mergesvgs extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        $rootPath  =  $directory->getRoot();
        $name = $input->getArgument(self::NAME_ARGUMENT);
        $option = $input->getOption(self::NAME_OPTION);
        $root = $rootPath.'/app/design/frontend/MageGuide';
        $Subdirs=[
            'TheMart' => '/TheMart/web',
            'TheMartMob' => '/TheMartMob/web',
            'TheMartTablet' => '/TheMartTablet/web',
        ];
        $fallback =  'TheMart';
        $images = '/images';
        $css = '/css';

        foreach ($Subdirs as $key => $subdir){
            $ImagesDir = $root.$subdir.$images;
            $RootImagesDir = $root.$subdir.'/';
            $FallbackRootImagesDir = $root.$Subdirs[$fallback].'/';;
            $cssDir = $root.$subdir.$css;
            $this->changeSvgs($ImagesDir, $key);
            $this->changeCss($cssDir, $key, $fallback, $RootImagesDir , $FallbackRootImagesDir);
        }
    }

    protected function changeCss($dir, $key, $fallback, $RootImagesDir , $FallbackRootImagesDir){
        $subdirs = array_filter(glob($dir.'/*'), 'is_dir');
        foreach ($subdirs as $subdir){
            $this->changeCss($subdir, $key, $fallback, $RootImagesDir , $FallbackRootImagesDir);
        }
        $handle = opendir($dir);
        while($file = readdir($handle)){
            $replace =false;
            $filename = $file.'_replaced_by_merged_'.date('Y_m_d-H_i_s');;
            if($file=='_extend.less'){
                $debug =true;
            }else{
                $debug =false;
            }
            if((substr($file, -2, 2) == 'ss') && $file !== $filename){
                $content =  file_get_contents($dir.'/'.$file);
                $replaced = $this->processCss($content, $key, $fallback, $RootImagesDir , $FallbackRootImagesDir);
                if ($content != $replaced){
                    $replace =true;
                }
            }
            if($replace){
                $myfile = fopen($dir.'/'.$filename, "w");
                fwrite($myfile, $content);
                fclose($myfile);    
                $myfile = fopen($dir.'/'.$file, "w");
                fwrite($myfile, $replaced);
                fclose($myfile);    
            }
        }
        closedir($handle);
    }

    protected function processCss($content, $key, $fallback, $RootImagesDir , $FallbackRootImagesDir){
        $expr = "/(images.*?)\/([^\/]*)\.svg/i";
        $subst = '/'.$key.'Merged.svg#$1';
        $content = preg_replace_callback(
            $expr,
            function ($matches) use ($key, $fallback, $RootImagesDir , $FallbackRootImagesDir) {
                if ($matches){
                    $file = $matches[0];
                    $filename = $RootImagesDir.$file;
                    if (array_key_exists(2, $matches)){
                        if(!in_array($matches[2],[$key."Merged",$fallback."Merged"])){
                            if (file_exists ( $filename )){
                                $used = $key;
                            }else{
                                $used = $fallback;
                            }    
                            return $matches[1].'/'.$used.'Merged.svg#'.$matches[2];
                        }    
                    }
                }
                return $matches[0];
            },
            $content
        );
        return $content;
    }

    protected function changeSvgs($dir, $key){
        $subdirs = array_filter(glob($dir.'/*'), 'is_dir');
        foreach ($subdirs as $subdir){
            $this->changeSvgs($subdir, $key);
        }
        $handle = opendir($dir);
        $filename= $key.'Merged.svg';
        $Merged ='<svg id="parent_merged" width="100%" height="100%" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline;" >
          <defs><style>svg {display: none;} svg:target {display: inline;} #parent_merged {display: inline;}</style></defs>'."\r\n\r\n";
        while($file = readdir($handle)){
            if((substr($file, -4, 4) == '.svg') && $file !== $filename ){
                $content =  file_get_contents($dir.'/'.$file);
                $id = substr($file, 0, strlen($file)-4);
                $Merged .= $this->processSvg($content, $id)."\r\n";
            }
        }
        $Merged .='</svg>';
        $myfile = fopen($dir.'/'.$filename, "w");
        fwrite($myfile, $Merged);
        fclose($myfile);
        closedir($handle);
        //shell_exec("svgo " . $dir.'/'.$filename);
    }

    protected function processSvg($content ,$id){
        $start =  strpos( strtolower($content) , '<svg'  ); 
        $end = strpos( strtolower($content) , '>', $start  )+1; 
        $tags = [
            'id'=>$id,
            'width'=> '100%',
            'height'=> '100%',
        ];
        if ($start !== false){
            $prefix = substr($content, $start, $end-$start);
            $rest = substr($content, $end, strlen($content)-$start);
            $rest= $this->uniqueIds($rest, $id);
            foreach ($tags as $key => $value){
                $prefix = $this->replaceTags($prefix, $key, $value);
            }    
            $content = $prefix.$rest;
        }
        return $content;
    }

    protected function replaceTags($content, $key, $value){
        if (strpos( strtolower($content) , ' '.$key.'="' ) ){
            $expr = '/ '.$key.'=".*?"/i';
            $subst = ' '.$key.'="'.$value.'"';
        }else{
            $expr = '/<svg/i';
            $subst = '<svg '.$key.'="'.$value.'" ';
        }
        return  preg_replace($expr, $subst , $content);
    }

    protected function uniqueIds($content, $file_id){
        $fields = ['id','class'];
        foreach ($fields as $field){
            $expr = "/{$field}=\"(.*?)\"/i";
            preg_match_all($expr, $content, $matches );
            if($matches[0]){
                foreach ($matches[0] as $key => $vals){
                    $id = $matches[1][$key];
                    $replaced = $id.'_'.$file_id;
                    $content = str_replace($id, $replaced, $content);
                }                 
            }           
        }
        return $content;
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mageguide_mergesvgs:mergesvgs");
        $this->setDescription("merge svg images and changes less files");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }
}
