<?php

namespace App\Repository;

use App\Entity\Cours;
use App\Entity\CoursTempsPasse;
use App\Entity\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CoursTempsPasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoursTempsPasse::class);
    }

    public function addTime(Etudiant $etudiant, Cours $cours, int $seconds): CoursTempsPasse
    {
        $seconds = max(0, min(120, $seconds));

        $row = $this->findOneBy([
            'etudiant' => $etudiant,
            'cours' => $cours,
        ]);

        if ($row === null) {
            $row = new CoursTempsPasse();
            $row->setEtudiant($etudiant);
            $row->setCours($cours);
            $this->getEntityManager()->persist($row);
        }

        $row->addSecondes($seconds);

        return $row;
    }

    /**
     * @return array<int, array{
     *   cours_id:int,
     *   cours_code:string,
     *   cours_titre:string,
     *   enrolled_count:int,
     *   total_minutes:int,
     *   avg_minutes:int
     * }>
     */
    public function findAverageMinutesByCourseForEnseignant(int $enseignantId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.id AS cours_id',
                'c.codeCours AS cours_code',
                'c.titre AS cours_titre',
                'COUNT(DISTINCT e.id) AS enrolled_count',
                'COALESCE(SUM(COALESCE(ctp.secondes, 0)), 0) AS total_seconds'
            )
            ->from(\App\Entity\Cours::class, 'c')
            ->innerJoin('c.enseignants', 'ens')
            ->leftJoin('c.etudiants', 'e')
            ->leftJoin(\App\Entity\CoursTempsPasse::class, 'ctp', 'WITH', 'ctp.cours = c AND ctp.etudiant = e')
            ->andWhere('ens.id = :enseignantId')
            ->setParameter('enseignantId', $enseignantId)
            ->groupBy('c.id')
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $enrolled = (int) $row['enrolled_count'];
            $totalSeconds = (int) $row['total_seconds'];
            $totalMinutes = $totalSeconds > 0 ? (int) ceil($totalSeconds / 60) : 0;
            $avgMinutes = $enrolled > 0 ? (int) round($totalMinutes / $enrolled) : 0;

            $result[] = [
                'cours_id' => (int) $row['cours_id'],
                'cours_code' => (string) $row['cours_code'],
                'cours_titre' => (string) $row['cours_titre'],
                'enrolled_count' => $enrolled,
                'total_minutes' => $totalMinutes,
                'avg_minutes' => $avgMinutes,
            ];
        }

        usort($result, static fn (array $a, array $b): int => $b['avg_minutes'] <=> $a['avg_minutes']);

        return $result;
    }
}
