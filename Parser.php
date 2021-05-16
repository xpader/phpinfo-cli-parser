<?php
namespace PhpInfoCliParser;

/**
 * CLI phpinfo() Parser and Render.
 *
 * @package PhpInfoParser
 * @author Pader<ypnow@163.com>
 */
class Parser
{

    /**
     * Parse phpinfo() as an array data struct
     *
     * @return array
     */
    public static function parse()
    {
        ob_start();
        phpinfo();
        $buf = ob_get_contents();
        ob_end_clean();

        $lines = explode("\n", $buf);
        $lines = array_slice($lines, 3);

        $parses = []; //type: blank, value, title, text, hr

        foreach ($lines as $row) {
            $li = count($parses) - 1;
            $lii = $li - 1;
            $pre = isset($parses[$li]) ? $parses[$li][0] : 'blank';

            if (trim($row) == '') {
                if ($pre == 'blank') {
                    //连续多个空行则忽略，最多只保留一个空行
                    $parses[$lii]['j'] = 1; //对上一个内容标记已跳过一个空行，主要用于多段内容连接时保持分段
                    continue;
                } else {
                    $parses[] = ['blank'];
                }
            } elseif (substr($row, 0, 1) != ' ' && strpos($row, ' => ') !== false) {
                $values = explode(' => ', $row, 3);

                //remove break line of values.
                if ($pre == 'blank' && isset($parses[$lii]) && $parses[$lii][0] == 'value' && substr($row, 0, 10) != 'Directive ') {
                    array_pop($parses);
                }

                $values = array_map(function($v) {
                    if ($v == 'no value') {
                        return "<i>$v</i>";
                    } else {
                        return $v;
                    }
                }, $values);

                $parses[] = array_merge(['value'], $values);
            } elseif ($pre == 'value') {
                //紧跟在值后的文本其实是上一个值的换行内容
                $parses[$li][count($parses[$li])-1] .= "\n$row";
            } elseif ($pre == 'blank' && strlen($row) < 29) {
                //一些特殊的标题设为 h1 等级
                $level = ($row == 'Configuration' || $row == 'PHP Credits') ? 1 : 2;
                $parses[] = ['title', $row, $level];
            } elseif (preg_match('/^_+$/', trim($row))) { //多个连续的下划线是 hr
                $parses[] = ['hr'];
            } elseif (preg_match('/^\s{15,}/', $row)) { //开头连续多个空格的文本其实是表头单行标题，这里只作 h3，因为处理有点麻烦
                $parses[] = ['title', trim($row), 3];
            } else {
                //将每行开头的连续空格替换为可显示空格，否则开头的空格会不显示（无缩进）
                if (preg_match('/^(\s+)/', $row, $matchs)) {
                    $row = str_replace(' ', '&nbsp;', $matchs[1]).ltrim($row);
                }
                if ($pre == 'text') {
                    $parses[$li][1] .= "<br/>$row";
                } elseif ($pre == 'blank' && isset($parses[$lii]) && $parses[$lii][0] == 'text') {
                    //对于 文本，空行，文本 的情况，这些文本实际要作为多个段落连接到一格中
                    if (!is_array($parses[$lii][1])) {
                        $parses[$lii][1] = [str_replace('<br/>', '', $parses[$lii][1])];
                        $parses[$lii][1][] = $row;
                    } elseif (isset($parses[$lii]['j'])) {
                        //在已经是多个段落的文本中，如果上一次跳过了一个换行，则开始分段
                        //主要处理“PHP License”中有多个段落，每具段落又使用换行的方式输出的问题
                        $parses[$lii][1][] = $row;
                        unset($parses[$lii]['j']);
                    } else {
                        //在已经是多个段落的文本中连续文本换行将作为单行字符串连在一起
                        $parses[$lii][1][count($parses[$lii][1])-1] .= $row;
                    }
                } else {
                    $parses[] = ['text', $row];
                }
            }
        }

        return $parses;
    }

    /**
     * Render cli phpinfo to HTML format.
     *
     * @return string
     */
    public static function render()
    {
        $data = self::parse();
        ob_start();
        include(__DIR__.'/phpinfo.php');
        $buf = ob_get_contents();
        ob_end_clean();

        return $buf;
    }

}