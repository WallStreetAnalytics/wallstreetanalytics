using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Globalization;
using System.IO;
using System.IO.Compression;
using System.Linq;
using System.Net;
using System.Text;
using System.Threading;
using System.Threading.Tasks;

namespace RegShoFinraDailyShorts
{
    class RegShoFinraDailyShorts
    {
        /***************************************************************
        * 
        *      FINRA Short File Data
        * 
        ****************************************************************/
        public string website = new string("http://regsho.finra.org/");
        public string filenamePart1 = new string("CNMSshvol");
        //FP2 is the date in YYYYMMDD format
        public string filenamePart3 = new string(".txt");
        public DateTime StartDate = DateTime.Now;
        public DateTime EndDate = new DateTime(1970, 01, 01);

        /***************************************************************
         * 
         *      Local Directory Data
         * 
         ****************************************************************/

        public string localDirectoryRoot = @"D:\Stocks\RegShoFinra\DailyShorts";

        /***************************************************************
         * 
         *      Concatenated String
         * 
         ****************************************************************/

        public string url = new string("");
        public string destination = new string("");

        /***************************************************************
         * 
         *      404 Error Handling
         *      
         *      The fail count needs to occur for ~two weeks straight before it stops.
         *      Stock Exchange is closed on weekends (see above) and holidays
         * 
         ****************************************************************/

        public bool ResultsNot404 = true;
        public int ResultsFailCount = 0;

        public RegShoFinraDailyShorts(DateTime Start, DateTime End, string Dir) 
        {
            destination = Dir;
            StartDate = Start;
            EndDate = End;
        }

        public void Pull()
        {
            while (ResultsNot404 && StartDate >= EndDate)
            {
                url = website + filenamePart1 + StartDate.ToString("yyyyMMdd") + filenamePart3;
                destination = localDirectoryRoot + filenamePart1 + StartDate.ToString("yyyyMMdd") + filenamePart3;

                if ((int) StartDate.DayOfWeek > 0 && (int) StartDate.DayOfWeek < 6) // Sunday & Saturday respectively
                {
                    Console.WriteLine("Pulling down file: {0}", url);

                    using (WebClient client = new WebClient())
                    {
                        try
                        {
                            client.DownloadFile(url, destination);
                            Console.WriteLine("\tSuccess! Downloading file {0}", url);
                            ResultsFailCount = 0;
                        }
                        catch (WebException wex)
                        {
                            if (((HttpWebResponse) wex.Response).StatusCode == HttpStatusCode.NotFound)
                            {
                                Console.WriteLine("Unable to download {0}.  404 Not found.", url);

                                ResultsFailCount++;
                                if (ResultsFailCount >= 10)
                                {
                                    ResultsNot404 = false;
                                }
                            }
                        }
                    }
                }
                else
                {
                    Console.WriteLine("Date {0} falls on weekend.", StartDate.ToString("yyyyMMdd"));
                }
                StartDate = StartDate.AddDays(-1);

                //Do not abuse those that give us the snackies.
                Thread.Sleep(50);
            }

            Console.WriteLine("Loop ended. {0} failures occurred ending on {1}", ResultsFailCount, StartDate);
        }



        public void IngestDirectoryIntoMSSQL()
        {
            Console.WriteLine("Ingesting FINRA Shorts from directory: {0}", localDirectoryRoot);
            string[] files = Directory.GetFiles(localDirectoryRoot, "*.txt", SearchOption.TopDirectoryOnly);

            for (int i = 0; i < files.Length - 1; i++)
            {
                Console.WriteLine("Ingesting file: {0}", files[i]);
                IngestSingleFileIntoMSSQL(files[i]);
            }

            Console.WriteLine("Operation completed.  Press any key to continue.");
            Console.ReadLine();
        }




        public void IngestSingleFileIntoMSSQL(string f)
        {
            try
            {
                //Create Connection to SQL Server
                SqlConnection SQLConnection = new SqlConnection();
                SQLConnection.ConnectionString = @"Data Source = DESKTOP-1786GJA\SQLEXPRESS; Database=stunks; Integrated Security=true;";

                System.IO.StreamReader SourceFile = new System.IO.StreamReader(f);

                string line = "";
                Int32 counter = 0;

                SQLConnection.Open();
                while ((line = SourceFile.ReadLine()) != null)
                {
                    //skip the header row and last row
                    if (counter > 0 && line.Length > line.Replace("|", "").Length)
                    {
                        //prepare insert query
                        string query = "INSERT INTO dbo.[FINRA_REGSHO_DAILYSHORTS] ([DATE], [SYMBOL], [SHORT_VOLUME], [SHORT_EXEMPT_VOLUME], [TOTAL_VOLUME], [MARKETS])" +
                                " Values (@DATE, @SYMBOL, @SHORT, @EXEMPT, @TOTAL, @MARKETS)";

                        DateTime parsedDate = DateTime.ParseExact(line.Split("|")[0], "yyyyMMdd", CultureInfo.InvariantCulture);

                        //execute sqlcommand to insert record
                        SqlCommand command = new SqlCommand(query, SQLConnection);
                        command.Parameters.AddWithValue("@date", parsedDate);
                        command.Parameters.AddWithValue("@symbol", line.Split("|")[1]);
                        command.Parameters.AddWithValue("@SHORT", line.Split("|")[2]);
                        command.Parameters.AddWithValue("@EXEMPT", line.Split("|")[3]);
                        command.Parameters.AddWithValue("@TOTAL", line.Split("|")[4]);
                        command.Parameters.AddWithValue("@MARKETS", line.Split("|")[5]);

                        Console.WriteLine("Ingesting File: {0}\nLine: {1}", f.ToString(), line.ToString());

                        int result = command.ExecuteNonQuery();

                        if (result < 0)
                        {
                            Console.WriteLine("Error writing following command to database:\n{0}", command);
                        }   
                    }
                    counter++;
                }

                SourceFile.Close();
                SQLConnection.Close();

            }
            catch (IOException Exception)
            {
                Console.Write(Exception);
            }
        }
    }
}
