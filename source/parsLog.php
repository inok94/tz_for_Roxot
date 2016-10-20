<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 17.10.2016
 * Time: 2:09
 * 
 * 
 * 
 * 1) берем файл json из matches 
 * 2) переобразуем в ассоциативный массив
 *
 *
 * написать функцию вывода игрокков команд (сложить в массив и обработать foreach)
 *
 *
 */
//создать вункцию под каждый тип
$content = '';

$content .= '
<html>
<head>
<title>Футбольный матч</title>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</head>
<body>
';

$obj = new parsLog('1024102');
$matches = $obj->getMatches();
$stadium = $obj->getStadium();
$content.= $stadium['county'].$stadium['city'].$stadium['stadium'];

$content.= '<br>';
$content.= $obj->getTitleTeams();
$content.= '<br>';
$content.= $obj->getGoals();

$teams = $obj->getTeams();

$content.= '<br><b>' . $teams['team1']['title'] . '</b><br>';
foreach ($obj->startPlayers['team1'] as $value){
    $content.= $value . '<br>';
}
$content.= '<br><b>' . $teams['team2']['title'] . '</b><br>';
foreach ($obj->startPlayers['team2'] as $value){
    $content.= $value  . '<br>';
}


$content.= "<table border='1'>";
$content.= "<tr>
    <th>time</th>
    <th>image</th>
    <th>description</th>
    
      </tr>";

foreach ($matches as $key => $row){
    $image = '';
    if(!empty($row['image']))
        $image =  "<img src='../img/" . $row['image']. "'>";


    $content.= "
        <td>$row[time]</td>
        <td>$image</td>
        <td>$row[description]</td>
          </tr>";

    }
    $content.= "</table>";

file_put_contents('../result/'. $obj->fileJson . '.html', $content);

class parsLog
{
    /**
     * @var array('time', 'image', 'description')
     */
    public $matches = array();

    /**
     * @var array('team1'=>  ,
     *              'team2')
     */
    public $teams = array();
    public $team1 = array();
    public $team2 = array();

    public $startPlayers = array();
    public $reservePlayers = array();
    public $playerYellowCard = array();

    public $fileJson;

    /**
     *
     * @var array('team1'=>3, 'team2'=>4)
     */
    public $goals = array('team1' => 0, 'team2' => 0);

    public $stadium;
    
    function  __construct($fileJson)
    {
        $this->fileJson = $fileJson;
        $this->getJson();
    }

    /**
     * @return array('time', 'image', 'description') ;
     *
     */
    public function getMatches(){
        return array_reverse($this->matches);
    }
    public function getStadium(){
        return $this->stadium;
    }

    public function getPlayers(){
        return $this->players;
    }
    public function  getTeams(){
        return $this->teams;
    }

    /**
     * вывод играка по id
     * @param $idPlayer
     * @param $teamId
     */
    public function getPlayerById($idPlayer, $teamId){
        $team = $this->$teamId;
        return $team[$idPlayer];
    }
    public function getTitleTeams()
    {
        return $this->teams['team1']['title'] . ' - ' . $this->teams['team2']['title'];
    }

    public function getGoals(){
        return $this->goals['team1'] . ' - ' . $this->goals['team2'];
    }


    public function combineTeams(){

        foreach ($this->teams['team1']['players'] as $value){
            $team1Keys[] = $value['number'];
            $team1Name[] = $value['name'];
        }
        $this->team1 = array_combine($team1Keys, $team1Name);

        foreach ($this->teams['team2']['players'] as $value){
            $team2Keys[] = $value['number'];
            $team2Name[] = $value['name'];
        }
        $this->team2 = array_combine($team2Keys, $team2Name);
    }

    /**
     * игрокои вышедшие на поле
     */
    public function startPlayers($team){
        $startPlayerNumbers = $this->teams[$team]['startPlayerNumbers'];

        foreach ($startPlayerNumbers as $value) {
            $this->startPlayers[$team][] = $this->getPlayerById($value, $team);
        }
    }

    public function getJson(){
        $fileJson = file_get_contents('matches/'.$this->fileJson.'.json');
        $json = json_decode($fileJson, true);
        //$json = array_reverse($json);
        foreach ($json as $key => $value){
           $this->type($value['type'], $value);
        }
    }


    function type($type, $value)
    {
        //array_reverse позже
        return $this->$type($value);// try/catch
    }

    function info($event)
    {
        //формируем и выводим данные
        $this->matches[] = array('time' => $event['time'],
                                'image' => '',
                                'description' => $event['description'],
                                ) ;
    }

    function startPeriod($event)
    {

        if(!empty($event['details'])) {
            /**
             * Заполнение информации о стадеоне.
             */
            $stadium = $event['details']['stadium'];
            $this->stadium = array('county' => $stadium['county'],
                'city' => $stadium['city'],
                'stadium' => $stadium['stadium']);

            /**
             * Заполнение информации о командах.
             */
            $this->teams = array('team1'=> $event['details']['team1']) + array('team2'=> $event['details']['team2']);
            $this->combineTeams();
            $this->startPlayers('team1');
            $this->startPlayers('team2');
        }
        $this->matches[] = array('time' => $event['time'],
            'image' => '',
            'description' => $event['description'],
        );

    }

    function finishPeriod($event)
    {
        $this->matches[] = array('time' => $event['time'],
            'image' => '',
            'description' => $event['description'],
        );
    }

    function dangerousMoment($event)
    {
        $this->matches[] = array('time' => $event['time'],
            'image' => '',
            'description' => $this->bold($event['description']),
        );
    }

//можно добавить красную карточку
    function yellowCard($event){
        $playerId = $playerId = $event['details']['playerNumber'];

        if (empty($this->playerYellowCard[$playerId])){
            $this->playerYellowCard[$playerId] = 0;
        }
        $this->playerYellowCard[$playerId] += 1;

        if ($this->playerYellowCard[$playerId] > 1){

            $image = 'yellow-red.gif';
            $text = 'получил вторую ж.к.';
        }else{
            $image = 'yellow.gif';
            $text = 'получил ж.к.';
        }

        $description = $this->bold( $event['details']['team'] . ' - ' .
                $this->getPlayerById($playerId, $this->getTeamByCity($event['details']['team'])) .
                ' (' .$event['details']['playerNumber'] .')  ' . $text . ' <br>') .
            $event['description']
        ;
        //$playerYellowCard[] = array($this->getPlayerById($event['details']['playerNumber']=> )
        $this->matches[] = array('time' => $event['time'],
            'image' => $image,
            'description' => $description,
        );
    }

    function goal($event)
    {
        //Имя команды
        $team = $event['details']['team'];
        //Номер игрока забившего гол
        $playerNumber = $event['details']['playerNumber'];
        //Номер помогающего игрока забившего гол
        $assistantNumber = $event['details']['assistantNumber'];
        $teamId = $this->getTeamByCity($event['details']['team']);
        //Добавляем гол команде
        $this->goals[$teamId] += 1;
        $description = $this->bold( $team . ' - ' .
            $this->goals['team1'] . ' : '  . $this->goals['team2'] . ' ' .
             $this->getPlayerById($playerNumber, $this->getTeamByCity($team)) . ' ('.  $playerNumber . ') ('.
                $this->getPlayerById($assistantNumber, $this->getTeamByCity($team)) . ') ('.  $assistantNumber . ')) ') . '<br>' .
         $event['description']
        ;
        $this->matches[] = array('time' => $event['time'],
            'image' => 'ball.png',
            'description' => $description,
        );
    }

    function replacePlayer($event)
    {
        //Название команды
        $team = $event['details']['team'];
        $player1 = $this->getPlayerById($event['details']['inPlayerNumber'], $this->getTeamByCity($team));
        $player2 = $this->getPlayerById($event['details']['outPlayerNumber'], $this->getTeamByCity($team));
        /**
         * Игроки которых заменили не присутствуют в json строке в игроках вышедших на поле
         *
        */
        //$this->startPlayers[$team][] = $player2;
        $description = $this->bold($team . ' - ' .$player1 . ' уходит с поля, ' . $player2 . ' вышел на поле'). '<br>' . $event['description'];
        $this->matches[] = array('time' => $event['time'],
            'image' => 'sub.gif',
            'description' => $description ,
        );
        //$replacePlayer = ($row['type'] === 'replacePlayer') ? $row['details']['team'] . $row['details']['inPlayerNumber'] . $row['details']['outPlayerNumber'] : '';

    }
    function bold($text){
        return '<b>' .$text . '</b>';
    }

    /**
     * возвращаем название команды
     * @param $nameTeam
     * @return mixed
     */
    function getTeamByCity($nameTeam)
    {
        foreach ($this->teams as $key => $value) {

            if ($value['title'] === $nameTeam) {
                $titleTeam = $key;
            }
        }
        return $titleTeam;
    }
}