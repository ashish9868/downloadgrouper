<?php

$env = file_get_contents(".env");
$vars = explode("\n", $env);
foreach($vars as $var){
    putenv($var);
}
$url = getenv('URL');
$groups = explode(",", getenv('GROUPS'));

$data = json_decode(file_get_contents($url));
$rows = [];
$size = 0;
$gbDivide = 1024 * 1024;
foreach ($data->files as $key => $file) {
    $originalName = explode("#", $file->url)[1] ?? null;
    $name = trim(substr($originalName, 0, strpos($originalName, "[")));   
    if ($originalName && $name) {
        preg_match_all('/\[(.*?)\]/', $originalName, $parts);
        $detail = $parts[1];
        if (count($detail) >= 1) {
            $rows[$name][] = [
                'name' => $originalName,
                'size' => $file->size,
                'id' => $detail[1] ?? '-NA',
                'version' => $detail[2] ?? '-NA-',
                'url' => $file->url
            ];
        } else {
            echo sprintf("%s is ignored\n", json_encode($parts[1]));
        }
    }
}
// individual
foreach($rows as $name => $items){
    $links = array_map(function($item) { return $item['url'];}, $items);
    file_put_contents("links/$name.txt", implode("\n\n", $links));
}

// group
foreach ($groups as $group) {
    $groupLinks = [];
    foreach ($rows as $name => $items) {
        if (stristr($name, $group)){
            $links = array_map(function ($item) {
                return $item['url'];
            }, $items);
            $groupLinks = array_merge($groupLinks, $links);
        }
    }
    if (count($groupLinks) > 0){
        file_put_contents(sprintf("links/link_group-%s.txt", $group), implode("\n\n", $groupLinks));
    }
}
?>
<style>
    table {
        width: 100%;
        border: solid 1px #eee;
        border-collapse: collapse;
    }

    table td {
        padding: 5px;
        border: solid 1px #eee;

    }
    td a{
        font-size: 12px;
    }
</style>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Version</th>
        <th>Links</th>
    </tr>
    <?php foreach ($rows as $name => $items): ?>
        <tr>
            <td><?php echo $name; ?> <a href="/links/<?php echo $name ?>.txt">Export Links</a></td>
            <td>
                <?php foreach ($items as $item): ?>
                    <?php $name = explode("#", $item['url'])[1] ?? null; ?>
                    <?php if ($name): ?>
                        <p><strong style="width: 150px;display:inline-block">[<?php echo number_format($item['size'] / (1024*1024), 2);  ?>MB]</strong><a href="<?php echo $item['url']; ?>"><?php echo $name;  ?></a></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </td>
        </tr>
    <?php endforeach; ?>

</table>