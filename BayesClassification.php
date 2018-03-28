<?php

/**
 * Created by PhpStorm.
 * User: Serhii
 * Date: 21.03.2018
 * Time: 1:25
 */
class BayesClassification
{
    //Набор обучающих данный
    private $trainDataSet = [];
    //Название поля, по которому классифицируем
    private $classField;
    //Массив возможных классов
    private $classes = [];

    //таблица повторений слов
    protected $tableWords = [];
    //таблица слов с вероятностью по каждому классу
    protected $tableWordsProbability = [];
    //количесвто повторений каждого слова
    protected $countWordsArray = [];
    //Таблица слов с нормированной вероятностью попадания в каждый класс
    protected $tableWordsNormalizedProbability = [];

    public function __construct(string $classLabel)
    {
        $this->classField = $classLabel;
    }

    //Разбивает входящую статью на слова и фильтруем их(Убираем слова длиной меньше 2 символов и слово the)
    private function splitAndFilterArticle(string $article):array
    {
        $article = strtolower($article);
        $words = preg_split('#(\s+)|[.,!?]#', $article);
        $filterWords = [];
        foreach ($words as $word) {
            //Убираем встречающиеся цифры и слова длиной меньше 2
            if (strlen($word) <= 2 || preg_match('#(^\d+$)|(^[tT]he$)#', $word)) continue;
            $filterWords[] = $word;
        }
        return $filterWords;
    }

    //Рассчитывает вероятность отношения слова к каждому из классов
    private function calculateProbability()
    {
        foreach ($this->tableWords as $word => $classes) {
            //sum хранит кол-во повторений слова
            $sum = 0;
            $this->tableWordsProbability[$word] = [];
            foreach ($classes as $class => $count) {
                $sum += $count;
            }
            //Кол-во повторений слова
            $this->countWordsArray[$word] = $sum;
            //Рассчитаем вероятноть отношения слова к классу
            foreach ($classes as $class => $count) {
                $this->tableWordsProbability[$word][$class] = $count / $sum;
            }
        }
    }

    //Рассчитываем нормированную вероятность отношения слова к каждому из классов
    private function calculateNormalizedProbability()
    {
        foreach ($this->tableWordsProbability as $word => $classes) {
            $this->tableWordsNormalizedProbability[$word] = [];
            foreach ($classes as $class => $probability) {
                $normalizedProbability = ($this->countWordsArray[$word] * $this->tableWordsProbability[$word][$class] +
                        $this->classes[$class]) / ($this->countWordsArray[$word] + 1);
                $this->tableWordsNormalizedProbability[$word][$class] = $normalizedProbability;
            }
        }
    }

    //Метод обучения
    public function teach(array $trainDataSet):void
    {
        $this->trainDataSet = $trainDataSet;
        //посчитаем кол-во встречающихся классов в обучающем наборе
        $count = count($this->trainDataSet);
        for ($i = 0; $i < $count; $i++) {
            if (!key_exists($this->trainDataSet[$i][$this->classField], $this->classes)) {
                $this->classes[$this->trainDataSet[$i][$this->classField]] = 0;
            }
            $this->classes[$this->trainDataSet[$i][$this->classField]]++;
        }

        //Вычислим вероятность каждого класса
        foreach ($this->classes as $class => $value) {
            $this->classes[$class] = $value / $count;
        }

        // Подсчитываем количество появлений слов в статье(Заполняем tableWords)
        //Массив, содержащий количество попаданий слова в определенный класс
        $probabilitiesCounter = [];
        foreach ($this->classes as $key => $value) {
            $probabilitiesCounter[$key] = 0;
        }
        for ($i = 0; $i < $count; $i++) {
            foreach ($this->trainDataSet[$i] as $fieldName => $value) {
                if ($fieldName == $this->classField) {
                    //Определяем имя класса
                    $className = $value;
                } else {
                    //Разобьем статью на слова
                    $filterWords = $this->splitAndFilterArticle($value);

                    //Строим таблицу слов с количеством их попадания в каждый класс
                    foreach ($filterWords as $filterWord) {
                        if (!key_exists($filterWord, $this->tableWords)) {
                            $this->tableWords[$filterWord] = $probabilitiesCounter;
                            $this->tableWords[$filterWord][$className]++;
                        } else {
                            $this->tableWords[$filterWord][$className]++;
                        }
                    }
                }
            }
        }

        //Вычислим вероятности отношения слова к конкретному классу
        $this->calculateProbability();

        //Вычислим нормированную вероятность отношения слова к классу
        $this->calculateNormalizedProbability();
        return;
    }

    //Метод классификации
    public function classify(array &$dataSet):array
    {
        //classProbabilities - массив вероятностей попадания статьи в опредленный класс
        $classProbabilities = [];
        foreach ($dataSet as &$set)
            foreach ($set as $key => $article) {
                //Разбиваем статью на слова
                $filterWords = $this->splitAndFilterArticle($article);

                foreach ($this->classes as $class => $probability) {
                    $classProbabilities[$class] = [];
                    //Начальное значение value = вероятносить класса
                    $value = $this->classes[$class];
                    foreach ($filterWords as $word) {
                        //Если данное слово есть в таблице обучающейся выборки:
                        if (array_key_exists($word, $this->tableWordsNormalizedProbability)) {
                            $value *= $this->tableWordsNormalizedProbability[$word][$class];
                        }
                    }
                    //Если значние отлично от начального, значит классу можно назначить вероятность, иначе 0
                    if ($value != $this->classes[$class])
                        $classProbabilities[$class] = $value;
                    else {
                        $classProbabilities[$class] = 0;
                    }
                }
                $className = '';
                $max = 0;
                //Находим класс с максимальной вероятностью
                foreach ($classProbabilities as $class => $value) {
                    if ($max < $value) {
                        $max = $value;
                        $className = $class;
                    }
                }
                if ($className == '') {
                    $set['class'] = "class is not defined";
                    echo "Sorry. Class is not defined!<br>";
                } else {
                    $set['class'] = $className;
                    echo $className.'<br>';
                }
             //   var_dump($classProbabilities);
                //var_dump($filterWords);
                //var_dump($this->tableWordsNormalizedProbability);
            }
        return $dataSet;
    }
}