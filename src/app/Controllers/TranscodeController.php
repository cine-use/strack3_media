<?php
/**
 * Created by PhpStorm.
 * User: weijer
 * Date: 2018/10/31
 * Time: 19:46
 */

namespace app\Controllers;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\X264;

class TranscodeController
{
    /**
     * FFMEPG转码对象
     * @var \FFMpeg\FFMpeg
     */
    protected $ffmpeg;

    /**
     * 初始化FFMPEG对象
     */
    protected function initFFMPEG()
    {
        $this->ffmpeg = \FFMpeg\FFMpeg::create([
            'ffmpeg.binaries' => STRACK_FFMPEG_DIR,
            'ffprobe.binaries' => STRACK_FFPROBE_DIR,
            'timeout' => 300, // The timeout for the underlying process
            'ffmpeg.threads' => 4,   // The number of threads that FFMpeg should use
        ]);
    }

    /**
     * 执行转码
     * @param $param
     */
    public function transCode($param)
    {
        $this->initFFMPEG();

        $video = $this->ffmpeg->open($param["source_path"]);
        $video->frame(TimeCode::fromSeconds($param['duration'] / 2))
            ->save($param["out_thumb_path"]);

        $videoFormat = new X264('aac', 'libx264');


        // 增加更多配置
        $additional = [];
        $additional[] = "-vprofile";
        $additional[] = "high";
        $additional[] = "-pix_fmt";
        $additional[] = "yuv420p";
        $additional[] = "-vf";
        $additional[] = "pad=ceil(iw/2)*2:ceil(ih/2)*2";

        $videoFormat->setAdditionalParameters($additional);


        $video->save($videoFormat, $param["out_video_path"]);

        // 转码成功删除源文件
        unlink($param["source_path"]);
    }
}