INSERT INTO main.exercises (tenant_id,name,status,default_sets,default_reps,default_tempo,default_rest,created_at,updated_at) VALUES
	 (1,'Barbell Bench Press','active',3,'10','3-1-1-0',60,'2026-03-31 18:07:00','2026-03-31 18:07:00'),
	 (1,'Machine Chest Press','active',3,'10','3-1-1-0',60,'2026-04-02 13:56:44','2026-04-02 13:56:44'),
	 (1,'Lat Pulldown','active',3,'10','3-1-1-0',60,'2026-04-02 15:08:04','2026-04-02 15:08:04');

INSERT INTO main.exercise_variations (exercise_id,variation_name,created_at,updated_at) VALUES
	 (1,'Incline','2026-04-01 05:09:34','2026-04-01 05:09:34'),
	 (1,'Dicline','2026-04-01 05:09:34','2026-04-01 05:09:34'),
	 (2,'Incline Press','2026-04-02 13:56:44','2026-04-02 13:56:44'),
	 (2,'Chest Press','2026-04-02 13:56:44','2026-04-02 13:56:44'),
	 (3,'Wide Grip','2026-04-02 15:08:04','2026-04-02 15:08:04'),
	 (3,'Close Grip','2026-04-02 15:08:04','2026-04-02 15:08:04'),
	 (3,'Underhand Grip','2026-04-02 15:08:04','2026-04-02 15:08:04'),
	 (3,'Neutral Grip','2026-04-02 15:08:04','2026-04-02 15:08:04'),
	 (3,'Single Arm','2026-04-02 15:08:04','2026-04-02 15:08:04'),
	 (3,'Medium Overhand Grip','2026-04-02 15:08:04','2026-04-02 15:08:04');
