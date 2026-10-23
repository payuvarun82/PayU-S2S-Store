<?php

declare(strict_types=1);

final class BankIcons
{
    /** PayU ibibo_code => official bank website domain for logo lookup. */
    private const DOMAINS = [
        '162B' => 'kotak.com',
        'ADBB' => 'andhrabank.in',
        'AIRNB' => 'airtel.in',
        'ALLB' => 'indianbank.in',
        'AUSFCNB' => 'aubank.in',
        'AUSFNB' => 'aubank.in',
        'AXIB' => 'axisbank.com',
        'AXISCNB' => 'axisbank.com',
        'BANDNB' => 'bandhanbank.com',
        'BBCB' => 'bankofbaroda.in',
        'BBKB' => 'bbkonline.com',
        'BBRB' => 'bankofbaroda.in',
        'BMNN' => 'indianbank.in',
        'BOIB' => 'bankofindia.co.in',
        'BOMB' => 'bankofmaharashtra.in',
        'CABB' => 'canarabank.com',
        'CBIB' => 'centralbankofindia.co.in',
        'CITNB' => 'citibank.co.in',
        'CPNB' => 'pnbindia.in',
        'CRPB' => 'unionbankofindia.co.in',
        'CSBN' => 'csb.co.in',
        'CSFBC' => 'capitalbank.co.in',
        'CSFBR' => 'capitalbank.co.in',
        'CSMSNB' => 'cosmosbank.com',
        'CUBB' => 'cityunionbank.com',
        'DBSB' => 'dbs.com',
        'DCBB' => 'dcbbank.com',
        'DENN' => 'bankofbaroda.in',
        'DLNBCORP' => 'dhanbank.com',
        'DLSB' => 'dhanbank.com',
        'DSHB' => 'db.com',
        'FEDB' => 'federalbank.co.in',
        'FEDCORP' => 'federalbank.co.in',
        'HDFB' => 'hdfcbank.com',
        'HDFCCONB' => 'hdfcbank.com',
        'ICIB' => 'icicibank.com',
        'ICICICNB' => 'icicibank.com',
        'IDBB' => 'idbi.com',
        'IDBICORP' => 'idbi.com',
        'IDFCNB' => 'idfcfirstbank.com',
        'INDB' => 'indianbank.in',
        'INDPOST' => 'indiapost.gov.in',
        'INGB' => 'hsbc.co.in',
        'INIB' => 'indusind.com',
        'INOB' => 'iob.in',
        'JAKB' => 'jkbank.com',
        'JANANB' => 'janabank.com',
        'JSBNB' => 'jsb.co.in',
        'KRKB' => 'karnatakabank.com',
        'KRVB' => 'karurvysya.com',
        'KRVBC' => 'karurvysya.com',
        'KTKBCORP' => 'kotak.com',
        'LVBD' => 'lvbank.com',
        'LVCB' => 'lvbank.com',
        'LVRB' => 'lvbank.com',
        'OBCB' => 'pnbindia.in',
        'PAYTMNB' => 'paytm.com',
        'PNBB' => 'pnbindia.in',
        'PSBNB' => 'punjabandsindbank.co.in',
        'RBL' => 'rblbank.com',
        'RBLCNB' => 'rblbank.com',
        'RBLNB' => 'rblbank.com',
        'RYBS' => 'rbs.com',
        'SBBJB' => 'onlinesbi.sbi',
        'SBHB' => 'onlinesbi.sbi',
        'SBIB' => 'onlinesbi.sbi',
        'SBMB' => 'onlinesbi.sbi',
        'SBNCORP' => 'onlinesbi.sbi',
        'SBPB' => 'onlinesbi.sbi',
        'SBTB' => 'onlinesbi.sbi',
        'SCBNB' => 'sc.com',
        'SDCB' => 'sc.com',
        'SOIB' => 'southindianbank.com',
        'SRSWT' => 'saraswatbank.com',
        'SVCB' => 'svcbank.com',
        'SVCNB' => 'svcbank.com',
        'SYDB' => 'canarabank.com',
        'SYNDB' => 'canarabank.com',
        'TBON' => 'nainitalbank.co.in',
        'TESTPGNB' => 'payu.in',
        'TMBB' => 'tamilnadmercantilebank.org.in',
        'UBIB' => 'unionbankofindia.co.in',
        'UBIBC' => 'unionbankofindia.co.in',
        'UCOB' => 'ucobank.com',
        'UCOCNB' => 'ucobank.com',
        'UNIB' => 'pnbindia.in',
        'VJYB' => 'unionbankofindia.co.in',
        'YESB' => 'yesbank.in',
    ];

    public static function url(string $ibiboCode, string $title = ''): string
    {
        $code = strtoupper(trim($ibiboCode));
        $domain = self::DOMAINS[$code] ?? self::guessDomain($title);

        if ($domain === '') {
            return '';
        }

        return self::faviconUrl($domain);
    }

    private static function faviconUrl(string $domain): string
    {
        $site = 'https://www.' . ltrim($domain, '.');

        return 'https://t1.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url='
            . rawurlencode($site)
            . '&size=64';
    }

    private static function guessDomain(string $title): string
    {
        $title = strtolower(trim($title));
        if ($title === '') {
            return '';
        }

        if (str_contains($title, 'axis')) {
            return 'axisbank.com';
        }
        if (str_contains($title, 'hdfc')) {
            return 'hdfcbank.com';
        }
        if (str_contains($title, 'icici')) {
            return 'icicibank.com';
        }
        if (str_contains($title, 'state bank')) {
            return 'onlinesbi.sbi';
        }
        if (str_contains($title, 'punjab national')) {
            return 'pnbindia.in';
        }
        if (str_contains($title, 'kotak')) {
            return 'kotak.com';
        }
        if (str_contains($title, 'yes bank')) {
            return 'yesbank.in';
        }
        if (str_contains($title, 'union bank')) {
            return 'unionbankofindia.co.in';
        }
        if (str_contains($title, 'canara')) {
            return 'canarabank.com';
        }
        if (str_contains($title, 'baroda')) {
            return 'bankofbaroda.in';
        }

        return '';
    }
}
