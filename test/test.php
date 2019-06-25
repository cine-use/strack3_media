<?php
require './../vendor/autoload.php';

$ffmpeg = FFMpeg\FFMpeg::create(array(
    'ffmpeg.binaries'  => '/mnt/hgfs/work-strack_G/strack_service/bin/natron/bin/ffmpeg',
    'ffprobe.binaries' => '/mnt/hgfs/work-strack_G/strack_service/bin/natron/bin/ffprobe',
    'timeout'          => 300, // The timeout for the underlying process
    'ffmpeg.threads'   => 4,   // The number of threads that FFMpeg should use
));

$video = $ffmpeg->open('/mnt/hgfs/work-strack_G/strack_service/test/test_mp4.mp4');

$videoData = $ffmpeg->getFFProbe()
    ->streams('test_mp4.mp4')
    ->videos()
    ->first()
    ->get("r_frame_rate");


$rate = (int)explode("/", $videoData)[0];
var_dump($rate);

//$video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(1))
//    ->save('sheep_frame.jpg');
//
$video->save(new FFMpeg\Format\Video\X264('aac'), 'export-sheep.mp4');