namespace HnCore\Support;

final class StringHelper
{
    public static function normalize(string $word): string
    {
        $word = trim(mb_strtolower($word));
        return preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', $word);
    }
}