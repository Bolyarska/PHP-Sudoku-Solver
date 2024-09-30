<?php

/*the code provides a single solution for a 9x9 sudoku puzzle. If the starting puzzle is invalid there won't be a solution.

The data is derived from the 'input' file and the solution and time taken to produce it is presented in the 'Result' file.

expected format for the matrix (example):

0 2 0 0 0 0 0 6 3
0 0 0 0 0 5 4 0 1
0 0 3 0 0 9 0 8 0
0 0 0 2 0 0 0 0 0
0 0 7 4 0 0 8 0 0
0 0 1 0 7 0 0 0 0
0 0 0 0 3 0 0 0 0
9 0 0 0 0 0 0 0 0
7 0 5 0 0 0 0 0 0

*/

class SudokuSolver
{

    // custom exit status codes
    const EXIT_FILE_NOT_FOUND = 1;
    const EXIT_INVALID_MATRIX_FORMAT = 2;
    const EXIT_ACCESS_DENIED = 3;
    const EXIT_CANNOT_SOLVE_BOARD = 4;

    private $matrix;

    public function __construct($matrix)
    {
        $this->matrix = $matrix; // Assigning the empty input matrix to the class property
    }

    protected function is_all_true($previous_result, $item)
    {
        return $previous_result && $item;
    }

    public function input_file_handler($file)
    {
        if (!file_exists($file)) { // checks if the file exists
            echo 'Input file does not exist.';
            exit(self::EXIT_FILE_NOT_FOUND);
        }

        $fileHandle = fopen($file, 'r'); // checks if the file can be opened

        if (!$fileHandle) {
            echo 'Failed to open the file.';
            exit(self::EXIT_ACCESS_DENIED);
        }

        $rowCount = 0;
        $matrix = []; // Create a new local variable for the matrix

        while (($line = fgets($fileHandle))) {
            $row = explode(' ', trim($line));
            $can_be_number = array_map('is_numeric', $row);
            if (count($row) == 9 && array_reduce($can_be_number, array($this, 'is_all_true'), true)) { // first and second item
                $matrix[] = array_map('intval', $row);
                $rowCount++;
            } else {
                echo 'Invalid input file format';
                exit(self::EXIT_INVALID_MATRIX_FORMAT);
            }
        }

        if ($rowCount !== 9) {
            echo 'Invalid input file format. Number of rows is not 9.';
            exit(self::EXIT_INVALID_MATRIX_FORMAT);
        }

        fclose($fileHandle);
        $this->matrix = $matrix; // Assigning the local filled matrix to the class property
        return;
    }

    protected function find_empty_cell($start_row, $start_col)
    {
        // Finds the first empty cell in the matrix starting from the last checked empty cell.
        // Returns the coordinates of the cell or null if no empty cell exists.
        for ($row = $start_row; $row < 9; $row++) {
            for ($col = $start_col; $col < 9; $col++) {
                if ($this->matrix[$row][$col] == 0) {
                    return [$row, $col];
                }
            }

            $start_col = 0;
        }
    }

    protected function get_possible_values($row, $col)
    {
        // Given the coordinates of an empty cell (row, col), determines the set of possible values that could be placed in that cell.
        // Returns a set of possible values for that cell.
        $possible_values = range(1, 9);

        // Remove values in the same row and column
        for ($i = 0; $i < 9; $i++) {
            $value = $this->matrix[$row][$i];
            $key = array_search($value, $possible_values);
            if ($key !== false) {
                unset($possible_values[$key]);
            }

            $value = $this->matrix[$i][$col];
            $key = array_search($value, $possible_values);
            if ($key !== false) {
                unset($possible_values[$key]);
            }
        }

        // Check the values in the same 3x3 box
        $box_row = floor($row / 3) * 3;
        $box_col = floor($col / 3) * 3;
        for ($i = $box_row; $i < $box_row + 3; $i++) {
            for ($j = $box_col; $j < $box_col + 3; $j++) {
                $value = $this->matrix[$i][$j];
                $key = array_search($value, $possible_values);
                if ($key !== false) {
                    unset($possible_values[$key]);
                }
            }
        }

        return $possible_values;
    }

    protected function solve($start_row = 0, $start_col = 0)
    {
        $empty_cell = $this->find_empty_cell($start_row, $start_col);
        if (!$empty_cell) {
            return true;
        }

        [$row, $col] = $empty_cell;
        $possible_values = $this->get_possible_values($row, $col);

        if (empty($possible_values)) {
            return false;
        }

        foreach ($possible_values as $value) {
            $this->matrix[$row][$col] = $value;

            if ($this->solve($row, $col + 1)) { // Recursion
                return true;
            }

            $this->matrix[$row][$col] = 0; // Backtracks the last cell if no solution is found with the current value
        }

        return false;
    }

    protected function display_board()
    {
        // Displays the solved matrix
        $result = '';
        for ($row = 0; $row < 9; $row++) {
            if ($row % 3 == 0 && $row != 0) {
                $result .= str_repeat("- ", 11) . "\n";
            }

            for ($i = 0; $i < 9; $i++) {
                if ($i % 3 == 0 && $i != 0) {
                    $result .= "|" . ' ';
                }

                if ($i == 8) {
                    $result .= strval($this->matrix[$row][$i]) . "\n";
                } else {
                    $result .= strval($this->matrix[$row][$i]) . ' ';
                }
            }
        }

        return $result;
    }

    // exit file handling

    public function exit_file_handler()
    {
        if (!$this->solve()) { // checks for a solution
            echo "Board cannot be solved.";
            exit(self::EXIT_CANNOT_SOLVE_BOARD);
        }

        $exit_file = fopen('Result', 'w'); // attempts to open the file to write in it

        if (!$exit_file) {
            echo "Exit file cannot be accessed.";
            exit(self::EXIT_ACCESS_DENIED);
        }

        fwrite($exit_file, "Solved Board:\n\n");
        $solution = $this->display_board();
        fwrite($exit_file, $solution);

        fclose($exit_file);
    }
}

$solver = new SudokuSolver([]);
$solver->input_file_handler('input');
$solver->exit_file_handler();

?>